import { reactive, toRefs } from 'vue'
import apiClient from '@/services/apiClient'
import { resolveMediaUrl } from '@/utils/url.js'

const state = reactive({
  loaded: false,
  loading: false,
  error: null,
  images: {}, // key -> url
})

let broadcastPending = false
let pendingDetail = null
function scheduleMediaBroadcast(detail = {}) {
  if (typeof window === 'undefined') return
  pendingDetail = pendingDetail ? { ...pendingDetail, ...detail } : { ...detail }
  if (broadcastPending) return
  broadcastPending = true
  const emit = () => {
    broadcastPending = false
    const detailPayload = pendingDetail || {}
    pendingDetail = null
    try { window.dispatchEvent(new CustomEvent('cms-media-updated', { detail: detailPayload })) } catch (_) {}
    if (detailPayload.requestResize) {
      try { window.dispatchEvent(new Event('resize')) } catch (_) {}
    }
  }
  if (typeof window.requestAnimationFrame === 'function') {
    window.requestAnimationFrame(emit)
  } else {
    setTimeout(emit, 0)
  }
}

let storageHooked = false
function hookStorageBroadcast() {
  if (storageHooked) return
  storageHooked = true
  try {
    if (typeof window !== 'undefined') {
      window.addEventListener('storage', (e) => {
        try {
          if (!e) return
          const k = e.key || ''
          // Only react to explicit bust signal to avoid cross-tab ping-pong.
          // Do NOT reload on 'cms_media_cache' writes because loadMedia() itself
          // writes that key (with a fresh timestamp), which would otherwise
          // trigger an endless back-and-forth between tabs.
          if (k === 'cms_media_cache_bust') {
            state.loaded = false
            state.loading = false
            loadMedia()
          }
        } catch (_) {}
      })
    }
  } catch (_) { /* ignore */ }
}

// Temporary default: force local media without hitting API.
// Configure with env vars (set one of these to "1" or "true"):
// - VITE_MEDIA_FORCE_LOCAL (Vite)
// - VUE_APP_MEDIA_FORCE_LOCAL (Vue CLI)
// You can also override at runtime via localStorage/query/global as below.
const FORCE_LOCAL_DEFAULT = (() => {
  try {
    // Read build-time envs via bundler replacement (no import.meta usage for Vue CLI compatibility)
    const v =
      // Vite-style env var if present (replaced at build time where supported)
      (typeof process !== 'undefined' && process && process.env && process.env.VITE_MEDIA_FORCE_LOCAL) ||
      // Vue CLI-style env var
      (typeof process !== 'undefined' && process && process.env && process.env.VUE_APP_MEDIA_FORCE_LOCAL) ||
      undefined
    if (typeof v !== 'undefined') return String(v) === '1' || String(v).toLowerCase() === 'true'
  } catch (_) {}
  // Backend media registry is ready by default.
  // If no env overrides are found, do NOT force local.
  return false
})()

function isForceLocal() {
  try {
    if (typeof window !== 'undefined') {
      if (FORCE_LOCAL_DEFAULT) return true
      const ls = localStorage.getItem('cms_media_force_local')
      if (ls && ls.toString() === '1') return true
      const qp = new URLSearchParams(window.location.search)
      if (qp.get('mediaLocal') === '1') return true
      // Allow a runtime global flag for debugging
      if (window.__CMS_MEDIA_FORCE_LOCAL__ === true) return true
    }
  } catch (_) {}
  return false
}

function readLocalMock() {
  try {
    // Highest priority: explicit images object for media registry
    const imgStr = localStorage.getItem('cms_mock_media_images')
    if (imgStr) {
      const imgs = JSON.parse(imgStr)
      if (imgs && typeof imgs === 'object') return imgs
    }
    // Backward compat: full mock payload with media.images
    const str = localStorage.getItem('cms_mock_data')
    const json = str ? JSON.parse(str) : null
    const images = json?.media?.images || null
    if (images) return images
  } catch (_) {}
  return null
}

// Persistent cache (SWR): keep last successful media map in localStorage
function readPersistentCache() {
  try {
    const raw = localStorage.getItem('cms_media_cache')
    if (!raw) return null
    const data = JSON.parse(raw)
    if (!data || typeof data !== 'object') return null
    const ttlMs = 5 * 60 * 1000 // 5 minutes TTL
    if (!data.ts || (Date.now() - data.ts) > ttlMs) return null
    const images = data.images
    return (images && typeof images === 'object') ? images : null
  } catch (_) { return null }
}

function writePersistentCache(images) {
  try {
    if (!images || typeof images !== 'object') return
    localStorage.setItem('cms_media_cache', JSON.stringify({ ts: Date.now(), images }))
  } catch (_) {}
}

async function loadMedia() {
  hookStorageBroadcast()
  if (state.loaded || state.loading) return
  state.loading = true
  state.error = null

  // Helper: normalize/resolve URLs in the map
  const normalize = (images) => {
    const out = {}
    if (images && typeof images === 'object') {
      for (const [k, v] of Object.entries(images)) {
        const meta = (v && typeof v === 'object') ? v : null
        let url = resolveMediaUrl(typeof v === 'string' ? v : (meta?.url || ''))
        // Add a lightweight cache-buster for API-served media endpoints to avoid stale CDN/browser caches
        try {
          if (typeof url === 'string' && url.startsWith('/api/public/m/')) {
            const ver = meta?.uploaded_at ? (Date.parse(meta.uploaded_at) || null) : null
            if (ver !== null) {
              url += (url.includes('?') ? '&' : '?') + '_t=' + encodeURIComponent(String(ver))
            }
          }
        } catch (_) { /* ignore */ }
        out[k] = url
      }
    }
    return out
  }

  // SWR: serve last-known-good images from persistent cache immediately
  try {
    const cached = readPersistentCache()
    if (cached) {
      state.images = normalize(cached)
      // keep loaded=false to allow network to refresh; UI can already render from cache
      try {
        scheduleMediaBroadcast({ reason: 'media-cache-hit', source: 'persistent-cache', requestResize: true })
      } catch (_) {}
    }
  } catch (_) { /* ignore */ }

  // Force-local mode: never hit API, use local mock or built-ins
  if (isForceLocal()) {
    try {
      const images = readLocalMock()
      if (images) {
        state.images = normalize(images)
        state.loaded = true
        scheduleMediaBroadcast({ reason: 'media-local-mock', source: 'local', requestResize: true })
        return
      }
      // Built-in defaults
      state.images = normalize({
        hero_economic_indicators: '/img/hero-image.png',
        hero_economic_statistics: '/img/hero-image.png',
        hero_publications: '/img/hero-image.png',
        hero_company_profile: '/img/hero-image.png',
        hero_privacy: '/img/hero-image.png',
        hero_terms: '/img/hero-image.png',
        hero_transaction_law: '/img/hero-image.png',
        hero_contact: '/img/hero-image.png',
        hero_glossary: '/img/hero-image.png',
        hero_membership: '/img/hero-image.png',
        hero_seminars_current: '/img/hero-image.png',
        hero_financial_reports: '/img/hero-image.png',
        hero_sitemap: '/img/hero-image.png',
        hero_consulting: '/img/hero-image.png',
        // Other common section backgrounds
        contact_section_bg: '/img/-----1-1.png',
      })
      state.loaded = true
      return
    } catch (e) {
      // fall through to normal flow if something odd happens
    }
  }

  try {
    // 1) If admin session exists, fetch the media page through admin API
    // Avoid hitting admin endpoint when not authenticated to prevent spurious 404 logs
    if (apiClient.isAdminAuthenticated()) {
      // cache-buster to avoid stale admin responses
      const adminPage = await apiClient.get('/api/admin/pages/media', { silent: true, params: { _t: Date.now() } })
      const page = adminPage?.data?.page || adminPage?.data?.data?.page || adminPage?.page
      const images = page?.content?.images || null
      if (images) {
        state.images = normalize(images)
        try { writePersistentCache(images) } catch (_) {}
        state.loaded = true
        try { scheduleMediaBroadcast({ reason: 'media-admin-sync', source: 'admin', requestResize: true }) } catch (_) {}
        return
      }
    }
  } catch (e) {
    // ignore and try public endpoint
  }

  try {
    // 2) Public endpoint (published media page)
    // cache-buster to avoid stale public responses
    const res = await apiClient.get('/api/public/pages/media', { silent: true, params: { _t: Date.now() } })
    const page = res?.data?.page || res?.data?.data?.page
    const images = page?.content?.images || null
    if (images) {
      state.images = normalize(images)
      try { writePersistentCache(images) } catch (_) {}
      state.loaded = true
      try { scheduleMediaBroadcast({ reason: 'media-public-sync', source: 'public', requestResize: true }) } catch (_) {}
      return
    }
    throw new Error('media page not found')
  } catch (e) {
    // 3) Fallback: local mock or built-in defaults
    try {
      const images = readLocalMock()
      if (images) {
        state.images = normalize(images)
        state.loaded = true
        return
      }
      // Built-in defaults
      state.images = normalize({
        hero_economic_indicators: '/img/hero-image.png',
        hero_economic_statistics: '/img/hero-image.png',
        hero_publications: '/img/hero-image.png',
        hero_company_profile: '/img/hero-image.png',
        hero_privacy: '/img/hero-image.png',
        hero_terms: '/img/hero-image.png',
        hero_transaction_law: '/img/hero-image.png',
        hero_contact: '/img/hero-image.png',
        hero_glossary: '/img/hero-image.png',
        hero_membership: '/img/hero-image.png',
        hero_seminars_current: '/img/hero-image.png',
        hero_financial_reports: '/img/hero-image.png',
        hero_sitemap: '/img/hero-image.png',
        hero_consulting: '/img/hero-image.png',
        // Other common section backgrounds
        contact_section_bg: '/img/-----1-1.png',
      })
      state.loaded = true
      scheduleMediaBroadcast({ reason: 'media-fallback', source: 'fallback', requestResize: true })
    } catch (err) {
      state.error = err?.message || 'Failed to load media registry'
    }
  } finally {
    state.loading = false
  }
}

export function invalidateMediaCache() {
  try {
    state.loaded = false
    state.loading = false
    state.error = null
    state.images = {}
    scheduleMediaBroadcast({ reason: 'media-cache-cleared', source: 'invalidate' })
  } catch (_) {}
}

export function useMedia() {
  const ensure = () => loadMedia()
  const getImage = (key, fallback = '') => {
    const v = state.images?.[key]
    return typeof v === 'string' && v.length ? v : resolveMediaUrl(fallback)
  }
  const viewport = () => {
    try {
      const w = typeof window !== 'undefined' ? window.innerWidth : 1200
      if (w <= 600) return 'mobile'
      if (w <= 1024) return 'tablet'
      return 'desktop'
    } catch (_) { return 'desktop' }
  }
  const getResponsiveImage = (key, fallback = '') => {
    const vp = viewport()
    const tryKeys = vp === 'mobile'
      ? [ `${key}_mobile`, `${key}_sm`, `${key}@mobile`, key ]
      : vp === 'tablet'
      ? [ `${key}_tablet`, `${key}_md`, key ]
      : [ key ]
    for (const k of tryKeys) {
      const v = state.images?.[k]
      if (typeof v === 'string' && v.length) return v
    }
    return resolveMediaUrl(fallback)
  }
  return {
    ...toRefs(state),
    ensure,
    getImage,
    getResponsiveImage,
  }
}

export default useMedia
