// Utilities to resolve media URLs for <img src>
// Keeps design stable by not forcing API base for frontend assets

import { getApiBaseUrl } from '@/config/api.js'

export function resolveMediaUrl(input) {
  if (!input) return ''
  const url = String(input)
  const lower = url.toLowerCase()

  const apiBaseUrl = getApiBaseUrl()

  // Replace localhost URLs with actual API host
  if (lower.startsWith('http://localhost') || lower.startsWith('https://localhost') || 
      lower.startsWith('http://127.0.0.1') || lower.startsWith('https://127.0.0.1')) {
    // Extract the path after the domain
    try {
      const urlObj = new URL(url)
      return `${apiBaseUrl}${urlObj.pathname}${urlObj.search}${urlObj.hash}`
    } catch (e) {
      // If URL parsing fails, try to extract path manually
      const pathMatch = url.match(/https?:\/\/[^\/]+(\/.*)/);
      if (pathMatch) {
        return `${apiBaseUrl}${pathMatch[1]}`
      }
    }
  }

  // Other absolute URLs and protocol-relative - return as-is
  if (lower.startsWith('http://') || lower.startsWith('https://') || url.startsWith('//')) {
    return url
  }

  // Storage URLs - prepend API host
  if (url.startsWith('/storage/')) {
    return `${apiBaseUrl}${url}`
  }
  if (url.startsWith('storage/')) {
    return `${apiBaseUrl}/${url}`
  }

  // Frontend-bundled assets
  if (url.startsWith('/img/') || url.startsWith('/images/') || url.startsWith('/assets/') || url.startsWith('/favicon')) {
    return url
  }

  // Heuristic: admin uploads for the media registry often name files like
  // "media-<key>-<timestamp>.<ext>" (when pageKey is "media") or
  // "company-profile-<key>-<timestamp>.<ext>" (when pageKey is "company-profile").
  // If a bare filename (no slash) slips through, map it to the expected
  // public disk location so it won't 404 on the frontend.
  // Examples:
  //   media-company_profile_staff_mizokami-1757837975.svg
  //   company-profile-company_profile_message-1757855317.svg
  if (!url.includes('/') && /\.(png|jpe?g|webp|gif|svg)$/i.test(url)) {
    if (lower.startsWith('media-')) {
      return `${apiBaseUrl}/storage/pages/media/${url}`
    }
    if (lower.startsWith('company-profile-')) {
      return `${apiBaseUrl}/storage/pages/company-profile/${url}`
    }
    // Fallback: assume it's on public disk root
    return `${apiBaseUrl}/storage/${url}`
  }

  // Other root-relative paths starting with / (like /publications/covers/...)
  // These need both API host and /storage/ prefix
  if (url.startsWith('/')) {
    return `${apiBaseUrl}/storage${url}`
  }

  // Fallback: assume public disk relative path (strip leading 'public/')
  const path = url.replace(/^public\//, '')
  return `${apiBaseUrl}/storage/${path}`
}

export default { resolveMediaUrl }
