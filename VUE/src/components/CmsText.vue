<template>
  <InlineEditable
    v-bind="$attrs"
    :tag="tag"
    :pageKey="pageKey"
    :fieldKey="fieldKey"
    :type="type"
    :allowEmpty="allowEmpty"
    :value="display"
  >
    {{ display }}
  </InlineEditable>
  
</template>

<script>
import { computed, onMounted, onBeforeUnmount } from 'vue'
import InlineEditable from '@/components/InlineEditable.vue'
import { usePageText } from '@/composables/usePageText'
import { useEditMode } from '@/composables/useEditMode'

export default {
  name: 'CmsText',
  components: { InlineEditable },
  inheritAttrs: false,
  props: {
    pageKey: { type: String, required: true },
    fieldKey: { type: String, required: true },
    type: { type: String, default: 'text' }, // 'text' | 'html' | 'link'
    tag: { type: String, default: 'span' },
    fallback: { type: String, default: '' },
    // When true, allow empty string to render as blank (no fallback)
    allowEmpty: { type: Boolean, default: false },
  },
  setup(props) {
    const pageText = usePageText(props.pageKey)
    // 管理APIは「プレビュー/編集中のみ」優先。通常閲覧は公開APIで十分（高速）。
    let loadOpts = {}
    try {
      const isBrowser = typeof window !== 'undefined'
      const { enabled } = useEditMode()
      let isPreview = false
      if (isBrowser) {
        const hash = window.location.hash || ''
        const qs = hash.includes('?') ? hash.split('?')[1] : (window.location.search || '').slice(1)
        const params = new URLSearchParams(qs)
        isPreview = !!(params.has('cmsPreview') || params.has('cmsEdit') || params.get('cmsPreview') === 'edit')
      }
      if (isPreview || (enabled && enabled.value)) loadOpts.preferAdmin = true
    } catch (_) {}
    pageText.load(loadOpts).catch(() => {})
    const display = computed(() => {
      if (props.type === 'html') return pageText.getHtml(props.fieldKey, props.fallback, { allowEmpty: props.allowEmpty })
      if (props.type === 'link') return pageText.getLink(props.fieldKey, props.fallback, { allowEmpty: props.allowEmpty })
      return pageText.getText(props.fieldKey, props.fallback, { allowEmpty: props.allowEmpty })
    })
    // Preview key broadcasting: helps admin editor collect keys used on page
    const hasPreview = () => {
      try {
        const hash = window.location.hash || ''
        const qs = hash.includes('?') ? hash.split('?')[1] : (window.location.search || '').slice(1)
        const params = new URLSearchParams(qs)
        return params.has('cmsPreview')
      } catch(_) { return false }
    }
    let handler = null
    onMounted(() => {
      if (!hasPreview()) return
      try {
        window.parent && window.parent.postMessage({ type: 'cms-key', pageKey: props.pageKey, fieldKey: props.fieldKey, value: (typeof display.value === 'string' ? display.value : '') }, '*')
      } catch(_) {}
      handler = (ev) => {
        const data = ev?.data || {}
        if (data && data.type === 'cms-list-keys') {
          if (!data.pageKey || data.pageKey === props.pageKey) {
            try {
              window.parent && window.parent.postMessage({ type: 'cms-key', pageKey: props.pageKey, fieldKey: props.fieldKey, value: (typeof display.value === 'string' ? display.value : '') }, '*')
            } catch(_) {}
          }
        }
      }
      try { window.addEventListener('message', handler) } catch(_) {}
    })
    onBeforeUnmount(() => { try { if (handler) window.removeEventListener('message', handler) } catch(_) {} handler = null })
    return { display }
  }
}
</script>
