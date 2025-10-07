// Small utility to load page content and safely read structured texts/links/images
// Provides fallback-first getters to avoid layout/XSS risks.
import { reactive, toRefs } from 'vue'
import { useEditMode } from '@/composables/useEditMode'
import apiClient from '@/services/apiClient'

// Simple sanitizer: allowlisted tags and attributes only
function sanitizeHtml(input = '') {
  if (typeof input !== 'string') return ''

  // Drop script/style tags entirely
  let html = input.replace(/<\/(?:script|style)>/gi, '')
  html = html.replace(/<(?:script|style)[^>]*>[\s\S]*?<\/(?:script|style)>/gi, '')

  // Remove on* event handlers
  html = html.replace(/\son[a-z]+\s*=\s*"[^"]*"/gi, '')
  html = html.replace(/\son[a-z]+\s*=\s*'[^']*'/gi, '')
  html = html.replace(/\son[a-z]+\s*=\s*[^\s>]+/gi, '')

  // Allowlist tags and prune others
  // Expanded to reflect common CMS content while staying safe
  const allowedTags = [
    'b','strong','i','em','u','br','p','ul','ol','li','a','img',
    'h1','h2','h3','h4','h5','h6','div','span','blockquote','code','pre','hr','section','header','footer'
  ]

  // Replace disallowed tags with their text content (strip tags)
  html = html.replace(/<\/?([a-z0-9-]+)([^>]*)>/gi, (match, tag, attrs) => {
    const t = String(tag || '').toLowerCase()
    if (!allowedTags.includes(t)) {
      // strip the whole tag
      return ''
    }

    if (t === 'a') {
      // keep only safe href, target, rel
      let href = ''
      const hrefMatch = attrs.match(/href\s*=\s*("([^"]*)"|'([^']*)'|([^\s>]+))/i)
      if (hrefMatch) {
        href = hrefMatch[2] || hrefMatch[3] || hrefMatch[4] || ''
      }
      // block javascript: and data: except images via / path
      const safe = href.startsWith('http://') || href.startsWith('https://') || href.startsWith('/')
      const safeHref = safe ? ` href="${href.replace(/"/g, '&quot;')}"` : ''
      return `<a${safeHref} target="_blank" rel="noopener noreferrer">`
    }

    if (t === 'img') {
      // allow only safe src and optional alt; drop others
      let src = ''
      let alt = ''
      const srcMatch = attrs.match(/src\s*=\s*("([^"]*)"|'([^']*)'|([^\s>]+))/i)
      if (srcMatch) {
        src = srcMatch[2] || srcMatch[3] || srcMatch[4] || ''
      }
      const altMatch = attrs.match(/alt\s*=\s*("([^"]*)"|'([^']*)')/i)
      if (altMatch) {
        alt = altMatch[2] || altMatch[3] || ''
      }
      const isSafeSrc = src.startsWith('https://') || src.startsWith('http://') || src.startsWith('/api/public/m/') || src.startsWith('/')
      if (!isSafeSrc) return ''
      const safeSrc = src.replace(/"/g, '&quot;')
      const safeAlt = (alt || '').replace(/</g,'').replace(/>/g,'')
      return `<img src="${safeSrc}" alt="${safeAlt}">`
    }

    // for others, drop attributes
    return `<${t}>`
  })

  // Close orphaned open tags for <a> in a naive way
  // This is intentionally simple and defensive; complex HTML should use CmsBlock
  return html
}

const state = reactive({
  pages: {}, // key -> { loading, error, page }
})

export function usePageText(pageKey) {
  if (!state.pages[pageKey]) {
    state.pages[pageKey] = { loading: false, error: null, page: null, _loadingPromise: null }
  }

  const load = async (options = {}) => {
    const { preferAdmin = false, force = false } = options || {}
    const entry = state.pages[pageKey]

    // If a request is in-flight, reuse it unless force is explicitly requested
    if (entry._loadingPromise && !force) {
      try { await entry._loadingPromise } catch (_) {}
      return
    }

    // If already loading without a tracked promise, short-circuit unless force
    if (entry.loading && !force) return

    entry.loading = true
    entry.error = null
    const { enabled } = useEditMode()
    entry._loadingPromise = (async () => {
    try {
      // In preview mode with admin auth, fetch via admin endpoint to see unpublished changes
      const isBrowser = typeof window !== 'undefined'
      // Detect preview/edit flags from hash or search (hash-mode friendly)
      let isPreview = false
      let forcePublic = false
      if (isBrowser) {
        try {
          const hash = window.location.hash || ''
          const qs = hash.includes('?') ? hash.split('?')[1] : (window.location.search || '').slice(1)
          const params = new URLSearchParams(qs)
          const hasPreview = params.has('cmsPreview')
          const isEdit = params.has('cmsEdit') || params.get('cmsPreview') === 'edit'
          isPreview = !!(hasPreview || isEdit)
          const fp = params.get('forcePublic')
          forcePublic = (fp === '1' || fp === 'true')
        } catch (_) {
          isPreview = (window.location.search || '').includes('cmsPreview=1')
        }
        try {
          const lp = localStorage.getItem('use_public_page_api')
          if (lp === '1' || lp === 'true') forcePublic = true
        } catch (_) { /* noop */ }
      }
      const adminToken = isBrowser ? (localStorage.getItem('admin_token') || '') : ''
      const isEditing = !!(enabled && enabled.value)

      // 1) Instant local cache hydration (no flicker)
      // Even when force is requested, prefer to render immediately from local cache
      // and then refresh from the network. This fixes multi‑second gaps while waiting
      // for the API when editors write page_content_cache in the same tab.
      if (isBrowser) {
        // Allow opt-out of cache via URL flag (?cmsNoCache=1 or ?noCache=1) for hard refresh
        let noCache = false
        try {
          const hash = window.location.hash || ''
          const qs = hash.includes('?') ? hash.split('?')[1] : (window.location.search || '').slice(1)
          const params = new URLSearchParams(qs)
          const nc = params.get('cmsNoCache') || params.get('noCache')
          noCache = (nc === '1' || nc === 'true')
        } catch (_) { /* noop */ }
        try {
          if (!noCache) {
            const raw = localStorage.getItem('page_content_cache:' + pageKey)
            if (raw) {
              const cached = JSON.parse(raw)
              if (cached && typeof cached === 'object') {
                entry.page = cached
              }
            }
          } else {
            try { localStorage.removeItem('page_content_cache:' + pageKey) } catch (_) {}
          }
        } catch (_) { /* ignore cache read errors */ }
      }

      // 2) Fetch fresh from API
      let res
      // Prefer admin endpoint when: explicit preferAdmin, or URL preview/edit, or edit mode, and token present
      if (!forcePublic && (preferAdmin || isPreview || isEditing) && adminToken) {
        // Use admin endpoint for preview to bypass is_published filter
        // Add cache-buster to avoid any intermediate caching returning stale data
        res = await apiClient.get(`/api/admin/pages/${pageKey}`, { silent: true, params: { _t: Date.now() } })
        // Fallback to public endpoint on failure (e.g., invalid/expired admin token in LS)
        if (!res || res.success === false) {
          try {
            res = await apiClient.get(`/api/public/pages/${pageKey}`, { silent: true, params: { _t: Date.now() } })
          } catch (_) { /* keep error */ }
        }
      } else {
        // Public endpoint for normal visitors (with cache-buster to avoid stale)
        res = await apiClient.get(`/api/public/pages/${pageKey}`, { silent: true, params: { _t: Date.now() } })
      }

      // Accept both wrapped and direct payload shapes
      const body = res?.data || res
      let page = body?.page || body?.data?.page || null
      entry.page = page

      // If we loaded via admin (preview/edit) but got empty or stale structured arrays
      // (e.g., history/staff not present or outdated), try public endpoint and merge the
      // missing or shorter sections. This prevents blank/older sections when admin cache is stale.
      try {
        const usingAdmin = ((preferAdmin || isPreview || isEditing) && adminToken && !forcePublic)
        if (usingAdmin) {
          const adminHist = Array.isArray(page?.content?.history) ? page.content.history : []
          const adminStaff = Array.isArray(page?.content?.staff) ? page.content.staff : []

          let needPub = !adminHist.length || !adminStaff.length
          let pubPage = null
          if (!needPub) {
            // We may still want public if it has a longer list than admin (stale snapshot)
            // Fetch public lazily to compare lengths
            const pubResp = await apiClient.get(`/api/public/pages/${pageKey}`, { silent: true, params: { _t: Date.now() } })
            const pubBody = pubResp?.data || pubResp
            pubPage = pubBody?.page || pubBody?.data?.page || null
            const pubHist = Array.isArray(pubPage?.content?.history) ? pubPage.content.history : []
            const pubStaff = Array.isArray(pubPage?.content?.staff) ? pubPage.content.staff : []
            const histIsStale = adminHist.length > 0 && pubHist.length > adminHist.length
            const staffIsStale = adminStaff.length > 0 && pubStaff.length > adminStaff.length
            needPub = histIsStale || staffIsStale
          }
          if (!pubPage && needPub) {
            const pub = await apiClient.get(`/api/public/pages/${pageKey}`, { silent: true, params: { _t: Date.now() } })
            const pubBody = pub?.data || pub
            pubPage = pubBody?.page || pubBody?.data?.page || null
          }
          if (pubPage && pubPage.content) {
            const pubHist = Array.isArray(pubPage.content.history) ? pubPage.content.history : []
            const pubStaff = Array.isArray(pubPage.content.staff) ? pubPage.content.staff : []
            let mergedContent = { ...(page?.content || {}) }
            // Merge history when admin is missing or shorter than public
            if ((!adminHist.length && pubHist.length) || (adminHist.length && pubHist.length > adminHist.length)) {
              mergedContent = { ...mergedContent, history: pubHist }
            }
            // Merge staff similarly (defensive)
            if ((!adminStaff.length && pubStaff.length) || (adminStaff.length && pubStaff.length > adminStaff.length)) {
              mergedContent = { ...mergedContent, staff: pubStaff }
            }
            const merged = { ...(page || {}), content: mergedContent }
            entry.page = merged
            page = merged
          }
        }
      } catch(_) { /* non-blocking */ }

      // 3) Write-through cache for next reload
      if (isBrowser && page) {
        try { localStorage.setItem('page_content_cache:' + pageKey, JSON.stringify(page)) } catch (_) {}
      }
    } catch (e) {
      entry.error = e?.message || 'Failed to load page content'
    } finally {
      state.pages[pageKey].loading = false
      entry._loadingPromise = null
    }
    })()
    await entry._loadingPromise
  }

  const getText = (key, fallback = '', options = {}) => {
    const allowEmpty = !!options.allowEmpty
    const page = state.pages[pageKey]?.page
    const texts = page?.content?.texts
    const val = texts && Object.prototype.hasOwnProperty.call(texts, key) ? texts[key] : undefined
    if (allowEmpty) {
      return typeof val === 'string' ? val : fallback
    }
    return (typeof val === 'string' && val.length > 0) ? val : fallback
  }

  const getHtml = (key, fallback = '', options = {}) => {
    const allowEmpty = !!options.allowEmpty
    const allowBodyFallback = options.allowBodyFallback !== false // only allow content.html fallback for 'body' key by default
    const page = state.pages[pageKey]?.page
    const htmls = page?.content?.htmls || page?.content?.rich || null
    let raw = htmls && Object.prototype.hasOwnProperty.call(htmls, key) ? htmls[key] : undefined
    // Fallback to texts[key] when htmls[key] is not provided (editor differences)
    if ((raw === undefined || raw === null || raw === '') && page?.content?.texts && Object.prototype.hasOwnProperty.call(page.content.texts, key)) {
      const tval = page.content.texts[key]
      if (typeof tval === 'string') raw = tval
    }
    // フォールバック: 'body' キーのみ単体の content.html を本文として扱う
    if ((raw === undefined || raw === null || raw === '') && key === 'body' && allowBodyFallback && typeof page?.content?.html === 'string') {
      raw = page.content.html
    }

    // 文字列でない場合は即フォールバック
    const primary = (typeof raw === 'string' && raw.trim().length > 0) ? raw : fallback
    let sanitized = sanitizeHtml(primary)

    // 許容しない場合、サニタイズ後の中身が「テキスト無し（タグのみ）」ならフォールバック
    if (!allowEmpty) {
      const noWhitespace = (typeof sanitized === 'string') ? sanitized.replace(/[\s\u00A0]/g, '') : ''
      const textOnly = noWhitespace.replace(/<[^>]*>/g, '')
      const isEffectivelyEmpty = textOnly.length === 0
      if (isEffectivelyEmpty) {
        sanitized = sanitizeHtml(typeof fallback === 'string' ? fallback : '')
      }
    }
    return sanitized
  }

  const getLink = (key, fallback = '', options = {}) => {
    const allowEmpty = !!options.allowEmpty
    const page = state.pages[pageKey]?.page
    const links = page?.content?.links
    const val = links && Object.prototype.hasOwnProperty.call(links, key) ? links[key] : undefined
    if (typeof val !== 'string') return fallback
    if (allowEmpty && val === '') return ''
    // Allow only app-relative or http(s)
    return val.startsWith('/') || val.startsWith('http://') || val.startsWith('https://') ? val : fallback
  }

  return {
    ...toRefs(state.pages[pageKey]),
    load,
    getText,
    getHtml,
    getLink,
  }
}

export default usePageText

// Utility: force-reload all pages currently present in the cache
export async function refreshLoadedPages(options = {}) {
  try {
    const keys = Object.keys(state.pages || {})
    for (const k of keys) {
      try {
        const inst = usePageText(k)
        await inst.load({ force: true, ...(options || {}) })
      } catch (_) { /* ignore individual failures */ }
    }
  } catch (_) { /* noop */ }
}
