// Lightweight API response cache (localStorage + TTL)
// Usage:
//   import apiCache from '@/services/apiCache'
//   const cached = apiCache.get('publications:list', 300) // 5 min
//   apiCache.set('publications:list', data)

const STORAGE_PREFIX = 'api:'

function nowSec() {
  return Math.floor(Date.now() / 1000)
}

function makeKey(key) {
  return `${STORAGE_PREFIX}${key}`
}

function get(key, maxAgeSec) {
  try {
    const raw = localStorage.getItem(makeKey(key))
    if (!raw) return null
    const parsed = JSON.parse(raw)
    if (!parsed || typeof parsed !== 'object') return null
    if (!parsed.t || typeof parsed.t !== 'number') return null
    if (maxAgeSec && maxAgeSec > 0) {
      if (parsed.t + maxAgeSec < nowSec()) return null
    }
    return parsed.v
  } catch (e) {
    return null
  }
}

function set(key, value) {
  try {
    const payload = JSON.stringify({ t: nowSec(), v: value })
    localStorage.setItem(makeKey(key), payload)
  } catch (e) {
    // ignore quota or serialization errors
  }
}

function del(key) {
  try { localStorage.removeItem(makeKey(key)) } catch (e) {}
}

export default { get, set, del }

