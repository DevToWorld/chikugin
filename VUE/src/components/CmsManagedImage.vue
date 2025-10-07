<template>
  <img :src="resolvedSrc || resolvedFallback" :alt="computedAlt" v-bind="$attrs" />
</template>

<script>
import { usePageText } from '@/composables/usePageText'
import { resolveMediaUrl } from '@/utils/url.js'

export default {
  name: 'CmsManagedImage',
  inheritAttrs: false,
  props: {
    pageKey: { type: String, required: true },
    fieldKey: { type: String, required: true },
    fallback: { type: String, default: '' },
    alt: { type: String, default: '' },
    fallbackKeys: { type: Array, default: () => [] },
    placeholderValues: { type: Array, default: () => [] },
  },
  data() {
    return {
      _pageText: null,
      _pageMedia: null,
      _media: null,
      imageVersion: 0,
      __onStorage: null,
      __onVis: null,
      __onMediaUpdated: null,
      __unwatchImages: null,
      __unwatchMediaImages: null,
      __unwatchMediaLoaded: null,
      
    }
  },
  computed: {
    computedAlt() {
      return this.alt || ''
    },
    resolvedFallback() {
      return this.fallback ? resolveMediaUrl(this.fallback) : ''
    },
    resolvedSrc() {
      const fromPage = this.getImageFromPage()
      if (fromPage) return fromPage
      const fromMedia = this.getImageFromMedia()
      if (fromMedia) return fromMedia
      return ''
    }
  },
  async mounted() {
    await this.init()
  },
  beforeDestroy() {
    this.teardown()
  },
  beforeUnmount() {
    this.teardown()
  },
  methods: {
    teardown() {
      try { if (this.__onStorage) window.removeEventListener('storage', this.__onStorage) } catch(_) {}
      try { if (this.__onVis) document.removeEventListener('visibilitychange', this.__onVis) } catch(_) {}
      try { if (this.__onMediaUpdated) window.removeEventListener('cms-media-updated', this.__onMediaUpdated) } catch(_) {}
      try { if (this.__onResize) window.removeEventListener('resize', this.__onResize) } catch(_) {}
      try { if (typeof this.__unwatchImages === 'function') this.__unwatchImages() } catch(_) {}
      try { if (typeof this.__unwatchMediaImages === 'function') this.__unwatchMediaImages() } catch(_) {}
      try { if (typeof this.__unwatchMediaLoaded === 'function') this.__unwatchMediaLoaded() } catch(_) {}
    },
    async init() {
      await this.setupPageText()
      await this.setupPageMedia()
      this.setupListeners()
      this.bumpVersion()
    },
    async setupPageText(force = false) {
      if (!this._pageText) {
        this._pageText = usePageText(this.pageKey)
      }
      await this.reloadPageContent(force)
      try {
        this.__unwatchImages = this.$watch(() => {
          const imgs = this._pageText?.page?.value?.content?.images
          if (!imgs) return ''
          try { return JSON.stringify(imgs) } catch(_) { return Object.keys(imgs).join('|') }
        }, () => {
          this.bumpVersion()
        })
      } catch(_) {}
    },
    async setupPageMedia(force = false) {
      try {
        if (!this._pageMedia) {
          const mod = await import('@/composables/usePageMedia')
          const { usePageMedia } = mod
          this._pageMedia = usePageMedia()
        }
        await this._pageMedia.ensure(this.pageKey, {
          preferAdmin: this.isAdminOrPreview(),
          force,
        })
        this._media = this._pageMedia._media
        if (this._media) {
          try {
            this.__unwatchMediaImages = this.$watch(() => {
              const imgs = this._media && this._media.images
              const val = imgs && (imgs.value !== undefined ? imgs.value : imgs)
              try { return val ? JSON.stringify(val) : '' } catch(_) { return val ? Object.keys(val).join('|') : '' }
            }, () => this.bumpVersion())
            this.__unwatchMediaLoaded = this.$watch(() => {
              const loaded = this._media && this._media.loaded
              return loaded && (loaded.value !== undefined ? loaded.value : loaded)
            }, () => this.bumpVersion())
          } catch(_) {}
        }
      } catch(_) {}
    },
    setupListeners() {
      this.__onStorage = (ev) => {
        const k = ev && ev.key ? String(ev.key) : ''
        if (k === 'page_content_cache:' + this.pageKey) this.reloadAll(true)
      }
      this.__onVis = () => {
        if (document.visibilityState === 'visible') this.reloadAll()
      }
      this.__onMediaUpdated = (ev) => {
        const detailKey = ev && ev.detail && ev.detail.pageKey
        if (!detailKey || detailKey === this.pageKey) this.reloadAll(true)
      }
      try { window.addEventListener('storage', this.__onStorage) } catch(_) {}
      try { document.addEventListener('visibilitychange', this.__onVis) } catch(_) {}
      try { window.addEventListener('cms-media-updated', this.__onMediaUpdated) } catch(_) {}
      // viewport changes should re-evaluate responsive keys
      this.__onResize = () => { this.bumpVersion() }
      try { window.addEventListener('resize', this.__onResize) } catch(_) {}
    },
    async reloadAll(force = false) {
      await Promise.all([
        this.reloadPageContent(force),
        this.setupPageMedia(force)
      ])
      this.bumpVersion()
    },
    async reloadPageContent(force = false) {
      if (!this._pageText) return
      const opts = {}
      if (this.isAdminOrPreview()) opts.preferAdmin = true
      if (force || this.isAdminOrPreview()) opts.force = true
      try {
        await this._pageText.load(opts)
      } catch(err) {
        if (!force) {
          try { await this._pageText.load({ ...opts, force: true }) } catch(_) {}
        }
      }
    },
    bumpVersion() {
      this.imageVersion = (this.imageVersion + 1) % Number.MAX_SAFE_INTEGER
    },
    isAdminOrPreview() {
      try {
        const isAdmin = !!localStorage.getItem('admin_token')
        const hash = window.location.hash || ''
        const qs = hash.includes('?') ? hash.split('?')[1] : (window.location.search || '').slice(1)
        const params = new URLSearchParams(qs)
        const isPreview = params.has('cmsPreview') || params.has('cmsEdit') || params.get('cmsPreview') === 'edit'
        return isAdmin || isPreview
      } catch(_) {
        return false
      }
    },
    viewport() {
      try {
        const w = typeof window !== 'undefined' ? window.innerWidth : 1200
        if (w <= 600) return 'mobile'
        if (w <= 1024) return 'tablet'
        return 'desktop'
      } catch(_) { return 'desktop' }
    },
    getImageFromPage() {
      void this.imageVersion
      try {
        const imgs = this._pageText?.page?.value?.content?.images
        if (!imgs || typeof imgs !== 'object') return null
        const placeholders = new Set(this.placeholderValues || [])
        const vp = this.viewport()
        const vpKeys = vp === 'mobile'
          ? [ `${this.fieldKey}_mobile`, `${this.fieldKey}_sm`, `${this.fieldKey}@mobile` ]
          : (vp === 'tablet' ? [ `${this.fieldKey}_tablet`, `${this.fieldKey}_md` ] : [])
        const tryKeys = [ ...vpKeys, this.fieldKey, ...(Array.isArray(this.fallbackKeys) ? this.fallbackKeys : []) ]
        for (const key of tryKeys) {
          if (!Object.prototype.hasOwnProperty.call(imgs, key)) continue
          const v = imgs[key]
          if (v && !this.isPlaceholder(v, placeholders)) return this.normalizeImage(v)
        }
        // last resort: even placeholder primary to keep DOM stable
        return this.normalizeImage(imgs[this.fieldKey])
      } catch(_) {
        return null
      }
    },
    getImageFromMedia() {
      void this.imageVersion
      try {
        if (this._pageMedia && this._pageMedia.getResponsiveSlot) {
          const val = this._pageMedia.getResponsiveSlot(this.fieldKey, this.fieldKey, null)
          if (val) return resolveMediaUrl(val)
        }
        if (this._media) {
          if (this._media.getResponsiveImage) {
            const val = this._media.getResponsiveImage(this.fieldKey, null)
            if (val) return resolveMediaUrl(val)
          }
          if (this._media.getImage) {
            const val = this._media.getImage(this.fieldKey, null)
            if (val) return resolveMediaUrl(val)
          }
        }
      } catch(_) {}
      return null
    },
    isPlaceholder(entry, placeholders) {
      if (!entry) return true
      if (typeof entry === 'string') return placeholders.has(entry)
      if (typeof entry === 'object' && entry.url) return placeholders.has(entry.url)
      return false
    },
    normalizeImage(entry) {
      if (!entry) return null
      if (typeof entry === 'string') return resolveMediaUrl(entry)
      if (typeof entry === 'object' && entry.url) {
        let url = entry.url
        try {
          if (url.startsWith('/storage/') && entry.uploaded_at) {
            const ver = Date.parse(entry.uploaded_at) || null
            if (ver !== null) {
              url += (url.includes('?') ? '&' : '?') + '_t=' + encodeURIComponent(String(ver))
            }
          }
        } catch(_) {}
        return resolveMediaUrl(url)
      }
      return null
    }
  }
}
</script>
