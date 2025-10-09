<template>
  <AdminLayout>
    <div class="dashboard">
      <!-- ページヘッダー -->
      <div class="page-header">
        <h1 class="page-title">経済統計レポート管理</h1>
        <button @click="openCreateModal" class="add-btn">新規追加</button>
      </div>

      <!-- 検索・フィルタセクション -->
      <div class="search-section">
        <div class="search-container">
          <span class="search-label">検索する</span>
          <input 
            v-model="searchKeyword" 
            type="text" 
            placeholder="レポートタイトルを入力"
            class="search-input"
            @keyup.enter="performSearch"
          >
          <button @click="performSearch" class="search-btn">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
              <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.415l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z" fill="currentColor"/>
            </svg>
          </button>
        </div>

        <!-- フィルタ -->
        <div class="filter-section">
          <div class="filter-group">
            <label>カテゴリ</label>
            <select v-model="selectedCategory" @change="loadReports" class="filter-select">
              <option value="">全て</option>
              <option value="quarterly">四半期経済レポート</option>
              <option value="annual">年次経済統計</option>
              <option value="regional">地域経済調査</option>
              <option value="industry">産業別統計</option>
            </select>
          </div>
          
          <div class="filter-group">
            <label>年</label>
            <select v-model="selectedYear" @change="loadReports" class="filter-select">
              <option value="">全て</option>
              <option v-for="year in years" :key="year" :value="year">{{ year }}年</option>
            </select>
          </div>

          <div class="filter-group">
            <label>ステータス</label>
            <select v-model="selectedStatus" @change="loadReports" class="filter-select">
              <option value="">全て</option>
              <option value="published">公開中</option>
              <option value="draft">下書き</option>
            </select>
          </div>
        </div>
      </div>

      <!-- データテーブル -->
      <div class="table-container">
        <div v-if="loading" class="loading">読み込み中...</div>
        <div v-else-if="error" class="error">{{ error }}</div>
        <table v-else class="data-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>タイトル</th>
              <th>カテゴリ</th>
              <th>年</th>
              <th>公開日</th>
              <th>特集</th>
              <th>公開</th>
              <th>DL数</th>
              <th>操作</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="report in reports" :key="report.id">
              <td>{{ report.id }}</td>
              <td class="title-cell">
                <div class="title-content">
                  <span class="title">{{ report.title }}</span>
                  <span class="pages">{{ report.pages }}ページ</span>
                </div>
              </td>
              <td>
                <span class="category-badge" :class="`category-${report.category}`">
                  {{ getCategoryName(report.category) }}
                </span>
              </td>
              <td>{{ report.year }}</td>
              <td>{{ formatDate(report.publication_date) }}</td>
              <td>
                <span 
                  :class="['status-badge', report.is_featured ? 'featured' : 'not-featured']"
                  @click="toggleFeatured(report)"
                >
                  {{ report.is_featured ? '特集' : '-' }}
                </span>
              </td>
              <td>
                <span 
                  :class="['status-badge', report.is_published ? 'published' : 'draft']"
                  @click="togglePublished(report)"
                >
                  {{ report.is_published ? '公開' : '下書き' }}
                </span>
              </td>
              <td>{{ report.download_count || 0 }}</td>
              <td class="actions">
                <button @click="editReport(report)" class="action-btn edit">編集</button>
                <button @click="deleteReport(report)" class="action-btn delete">削除</button>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- ページネーション -->
        <div v-if="pagination.total_pages > 1" class="pagination">
          <button 
            @click="changePage(pagination.current_page - 1)"
            :disabled="pagination.current_page <= 1"
            class="pagination-btn"
          >
            前へ
          </button>
          
          <span class="pagination-info">
            {{ pagination.current_page }} / {{ pagination.total_pages }}
          </span>
          
          <button 
            @click="changePage(pagination.current_page + 1)"
            :disabled="pagination.current_page >= pagination.total_pages"
            class="pagination-btn"
          >
            次へ
          </button>
        </div>
      </div>

      <!-- 統計情報 -->
      <div class="stats-section">
        <div class="stats-grid">
          <div class="stat-card">
            <div class="stat-number">{{ stats.total || 0 }}</div>
            <div class="stat-label">総レポート数</div>
          </div>
          <div class="stat-card">
            <div class="stat-number">{{ stats.published || 0 }}</div>
            <div class="stat-label">公開中</div>
          </div>
          <div class="stat-card">
            <div class="stat-number">{{ stats.featured || 0 }}</div>
            <div class="stat-label">特集レポート</div>
          </div>
          <div class="stat-card">
            <div class="stat-number">{{ stats.total_downloads || 0 }}</div>
            <div class="stat-label">総ダウンロード数</div>
          </div>
        </div>
      </div>
    </div>

    <!-- レポート作成・編集モーダル -->
    <div v-if="showModal" class="modal-overlay" @click="closeModal">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h2>{{ editingReport ? 'レポート編集' : 'レポート新規作成' }}</h2>
          <button @click="closeModal" class="modal-close">&times;</button>
        </div>
        
        <form @submit.prevent="saveReport" class="modal-form">
          <div class="form-row">
            <div class="form-group">
              <label>タイトル <span class="required">*</span></label>
              <input 
                v-model="formData.title" 
                type="text" 
                required 
                class="form-input"
                placeholder="レポートタイトルを入力"
              >
            </div>
          </div>

          <div class="form-row">
            <div class="form-group half">
              <label>カテゴリ <span class="required">*</span></label>
              <select v-model="formData.category" required class="form-select">
                <option value="">選択してください</option>
                <option value="quarterly">四半期経済レポート</option>
                <option value="annual">年次経済統計</option>
                <option value="regional">地域経済調査</option>
                <option value="industry">産業別統計</option>
              </select>
            </div>
            
            <div class="form-group half">
              <label>年 <span class="required">*</span></label>
              <input 
                v-model="formData.year" 
                type="number" 
                required 
                min="2000" 
                max="2030"
                class="form-input"
              >
            </div>
          </div>

          <div class="form-row">
            <div class="form-group half">
              <label>公開日 <span class="required">*</span></label>
              <input 
                v-model="formData.publication_date" 
                type="date" 
                required 
                class="form-input"
              >
            </div>
            
            <!-- ページ数はUI上は非表示にする要望 -->
            <div class="form-group half" v-if="false">
              <label>ページ数</label>
              <input 
                v-model="formData.pages" 
                type="number" 
                min="0"
                class="form-input"
              >
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>説明</label>
              <textarea 
                v-model="formData.description" 
                class="form-textarea"
                rows="4"
                placeholder="レポートの説明を入力"
              ></textarea>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group half">
              <label>著者</label>
              <input 
                v-model="formData.author" 
                type="text" 
                class="form-input"
                placeholder="著者名"
              >
            </div>
            
            <div class="form-group half">
              <label>発行者</label>
              <input 
                v-model="formData.publisher" 
                type="text" 
                class="form-input"
                placeholder="発行者名"
              >
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>キーワード</label>
              <input 
                v-model="formData.keywords" 
                type="text" 
                class="form-input"
                placeholder="キーワード（カンマ区切り）"
              >
            </div>
          </div>

          <!-- ファイルアップロード（2カラムレイアウト） -->
          <div class="form-row file-upload-row">
            <!-- カバー画像アップロード -->
            <div class="form-group half">
              <label>カバー画像 <span class="required" v-if="!editingReport">*</span></label>
              
              <!-- 既存画像プレビュー（編集時、新規ファイル未選択時のみ） -->
              <div v-if="editingReport && formData.cover_image && !coverImageFile" class="file-preview">
                <div class="preview-image-container">
                  <img :src="getImageUrl(formData.cover_image)" alt="カバー画像" class="preview-image" />
                  <div class="preview-info">
                    <span class="preview-label">現在の画像</span>
                  </div>
                </div>
              </div>

              <!-- 新規画像選択時のプレビュー -->
              <div v-if="coverImageFile" class="file-preview">
                <div class="preview-image-container">
                  <img :src="coverImagePreview" alt="新しい画像" class="preview-image" />
                  <div class="preview-info">
                    <span class="preview-label">✓ 新しい画像が選択されました</span>
                    <span class="preview-detail">{{ coverImageFile.name }} ({{ formatFileSize(coverImageFile.size) }})</span>
                  </div>
                </div>
              </div>

              <!-- ファイル選択 -->
              <div class="file-upload-wrapper">
              <input 
                @change="handleCoverImageChange" 
                type="file" 
                accept="image/*"
                class="form-file"
                  id="cover-image-input"
              >
                <label for="cover-image-input" class="file-upload-btn">
                  {{ coverImageFile ? '別の画像を選択' : (editingReport ? '画像を変更' : '画像を選択') }}
                </label>
              </div>
              <p class="form-help">推奨: 2MB以下の画像ファイル（JPEG, PNG, GIF）</p>
            </div>
            
            <!-- レポートファイルアップロード -->
            <div class="form-group half">
              <label>レポートファイル (PDF) <span class="required" v-if="!editingReport">*</span></label>
              
              <!-- 既存ファイル情報（編集時、新規ファイル未選択時のみ） -->
              <div v-if="editingReport && formData.file_url && !reportFile" class="file-info-card">
                <div class="file-icon">
                  <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M14 2v6h6M16 13H8M16 17H8M10 9H8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                </div>
                <div class="file-details">
                  <div class="file-name">{{ formData.file_name || getFileName(formData.file_url) }}</div>
                  <div class="file-meta">
                    <span v-if="formData.formatted_file_size">{{ formData.formatted_file_size }}</span>
                    <span v-else-if="formData.file_size">{{ formData.file_size }} MB</span>
                    <span v-if="formData.pages"> • {{ formData.pages }}ページ</span>
                  </div>
                </div>
              </div>

              <!-- 新規ファイル選択時の情報 -->
              <div v-if="reportFile" class="file-info-card new-file">
                <div class="file-icon">
                  <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M14 2v6h6M16 13H8M16 17H8M10 9H8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                </div>
                <div class="file-details">
                  <div class="file-name">✓ {{ reportFile.name }}</div>
                  <div class="file-meta">{{ formatFileSize(reportFile.size) }}</div>
                </div>
              </div>

              <!-- ファイル選択 -->
              <div class="file-upload-wrapper">
              <input 
                @change="handleFileChange" 
                type="file" 
                accept=".pdf,.doc,.docx"
                class="form-file"
                  id="report-file-input"
              >
                <label for="report-file-input" class="file-upload-btn">
                  {{ reportFile ? '別のファイルを選択' : (editingReport ? 'ファイルを変更' : 'ファイルを選択') }}
                </label>
              </div>
              <p class="form-help">推奨: 10MB以下のPDFファイル</p>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group half">
              <label>対象会員レベル</label>
              <select v-model="formData.membership_level" class="form-select">
                <option value="free">一般公開（無料）</option>
                <option value="standard">スタンダード会員以上</option>
                <option value="premium">プレミアム会員のみ</option>
              </select>
            </div>
            <div class="form-group half">
              <label>会員限定（要ログイン）</label>
              <div class="checkbox-group">
                <label class="checkbox-label">
                  <input v-model="formData.members_only" type="checkbox">
                  会員限定にする
                </label>
              </div>
            </div>
          </div>

          <!-- チェックボックス群は別行に配置して左寄せ -->
          <div class="form-row">
            <div class="form-group full">
              <label>公開設定</label>
              <div class="checkbox-group">
                <label class="checkbox-label">
                  <input v-model="formData.is_downloadable" type="checkbox">
                  ダウンロード可能
                </label>
                <label class="checkbox-label">
                  <input v-model="formData.is_featured" type="checkbox">
                  特集レポート
                </label>
                <label class="checkbox-label">
                  <input v-model="formData.is_published" type="checkbox">
                  公開する
                </label>
              </div>
            </div>
          </div>

          <div class="modal-footer">
            <button type="button" @click="closeModal" class="btn-cancel">キャンセル</button>
            <button type="submit" :disabled="saving" class="btn-save">
              {{ saving ? '保存中...' : '保存' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </AdminLayout>
</template>

<script>
import AdminLayout from './AdminLayout.vue'
import { getApiUrl } from '@/config/api'
import { useAdminAuth } from '../../composables/useAdminAuth'
import apiClient from '../../services/apiClient'
import { resolveMediaUrl } from '@/utils/url.js'

export default {
  name: 'EconomicReportManagement',
  components: {
    AdminLayout
  },
  setup() {
    const { token, adminUser } = useAdminAuth()
    return { token, adminUser }
  },
  data() {
    return {
      reports: [],
      searchKeyword: '',
      selectedCategory: '',
      selectedYear: '',
      selectedStatus: '',
      loading: false,
      error: '',
      showModal: false,
      editingReport: null,
      saving: false,
      pagination: {
        current_page: 1,
        total_pages: 1,
        total: 0
      },
      stats: {},
      years: [],
      formData: {
        title: '',
        description: '',
        category: 'quarterly',
        year: new Date().getFullYear(),
        publication_date: new Date().toISOString().split('T')[0],
        author: 'ちくぎん地域経済研究所',
        publisher: '株式会社ちくぎん地域経済研究所',
        keywords: '',
        pages: 0,
        is_downloadable: true,
        // free の時は一般公開 → members_only=false
        members_only: false,
        membership_level: 'free',
        is_featured: false,
        is_published: false
      },
      coverImageFile: null,
      coverImagePreview: null,
      reportFile: null
    }
  },
  async mounted() {
    await this.loadYears()
    await this.loadReports()
    await this.loadStats()
  },
  methods: {
    async loadReports() {
      this.loading = true
      this.error = ''
      
      try {
        const params = {
          page: this.pagination.current_page,
          per_page: 20
        }
        
        if (this.searchKeyword) params.search = this.searchKeyword
        if (this.selectedCategory) params.category = this.selectedCategory
        if (this.selectedYear) params.year = this.selectedYear
        if (this.selectedStatus) params.status = this.selectedStatus
        
        const queryString = new URLSearchParams(params).toString()
        const response = await apiClient.get(`/api/admin/economic-reports${queryString ? '?' + queryString : ''}`)
        
        if (response.success && response.data) {
          this.reports = response.data.reports || []
          this.pagination = response.data.pagination || { current_page: 1, total_pages: 1, total: 0 }
        } else {
          this.error = response.error || 'レポートの読み込みに失敗しました'
        }
      } catch (err) {
        this.error = 'ネットワークエラーが発生しました'
        console.error('Load reports error:', err)
      } finally {
        this.loading = false
      }
    },

    async loadStats() {
      try {
        const response = await apiClient.get('/api/admin/economic-reports/stats/overview')
        
        if (response.success && response.data) {
          this.stats = response.data
        }
      } catch (err) {
        console.error('Load stats error:', err)
      }
    },

    async loadYears() {
      const currentYear = new Date().getFullYear()
      this.years = Array.from({ length: 11 }, (_, i) => currentYear - i)
    },

    performSearch() {
      this.pagination.current_page = 1
      this.loadReports()
    },

    changePage(page) {
      if (page >= 1 && page <= this.pagination.total_pages) {
        this.pagination.current_page = page
        this.loadReports()
      }
    },

    openCreateModal() {
      this.editingReport = null
      this.resetFormData()
      this.showModal = true
    },

    editReport(report) {
      this.editingReport = report
      this.formData = { ...report }
      this.formData.publication_date = this.formatDateForInput(report.publication_date)
      
      // Reset file selection when editing
      this.coverImageFile = null
      this.coverImagePreview = null
      this.reportFile = null
      
      // Clean up any existing preview URL
      if (this.coverImagePreview) {
        try {
          URL.revokeObjectURL(this.coverImagePreview)
        } catch (e) {
          // Ignore errors
        }
      }
      
      this.showModal = true
    },

    closeModal() {
      this.showModal = false
      this.editingReport = null
      this.resetFormData()
      this.coverImageFile = null
      this.coverImagePreview = null
      this.reportFile = null
      
      // Clean up preview URL to avoid memory leaks
      if (this.coverImagePreview) {
        try {
          URL.revokeObjectURL(this.coverImagePreview)
        } catch (e) {
          // Ignore errors
        }
      }
    },

    resetFormData() {
      this.formData = {
        title: '',
        description: '',
        category: 'quarterly',
        year: new Date().getFullYear(),
        publication_date: new Date().toISOString().split('T')[0],
        author: 'ちくぎん地域経済研究所',
        publisher: '株式会社ちくぎん地域経済研究所',
        keywords: '',
        pages: 0,
        is_downloadable: true,
        members_only: false,
        membership_level: 'free',
        is_featured: false,
        is_published: false
      }
    },

    async saveReport() {
      this.saving = true
      
      try {
        // クライアント側ガード（title必須）
        if (!this.formData.title || !String(this.formData.title).trim()) {
          this.saving = false
          alert('タイトルは必須です')
          return
        }

        const formData = new FormData()
        // クライアント側で最低限の整形（422対策）
        const allowedCategories = ['quarterly','annual','regional','industry']
        const payload = { ...this.formData }
        payload.title = String(payload.title).trim()
        if (!allowedCategories.includes(String(payload.category || '').trim())) {
          payload.category = 'quarterly'
        }
        // 発行日を YYYY-MM-DD に整形
        const dateStr = String(payload.publication_date || '').trim()
        if (!/^\d{4}-\d{2}-\d{2}$/.test(dateStr)) {
          try { payload.publication_date = new Date(dateStr).toISOString().split('T')[0] } catch(_) {}
          if (!/^\d{4}-\d{2}-\d{2}$/.test(String(payload.publication_date||''))) {
            payload.publication_date = new Date().toISOString().split('T')[0]
          }
        }
        // 数値化
        payload.year = parseInt(payload.year, 10) || new Date().getFullYear()
        payload.pages = parseInt(payload.pages, 10) || 0
        // booleanはそのままでも可だが文字列化
        payload.is_downloadable = !!payload.is_downloadable
        payload.is_featured = !!payload.is_featured
        payload.is_published = !!payload.is_published
        // 互換: members_only と membership_level の整合（最低限のガード）
        if (payload.membership_level && !['free','standard','premium'].includes(String(payload.membership_level))) {
          payload.membership_level = 'free'
        }

        // Exclude file fields from payload to avoid sending string data
        const fileFields = ['cover_image', 'file', 'file_url', 'file_name', 'file_size', 'file_size_bytes', 'formatted_file_size']

        Object.keys(payload).forEach(key => {
          const val = payload[key]
          if (val !== null && val !== undefined) {
            // Skip empty strings for optional fields
            if (typeof val === 'string' && val.trim() === '' && ['description', 'keywords', 'author', 'publisher'].includes(key)) {
              return
            }
            // Skip file-related fields - they will be handled separately if they are File objects
            if (fileFields.includes(key)) {
              console.log('Skipping file field:', key, val)
              return
            }
            formData.append(key, typeof val === 'boolean' ? (val ? '1' : '0') : val)
          }
        })
        
        // ファイルを追加（新規アップロードがある場合のみ）
        // 注意: 編集時に新しいファイルをアップロードしない場合は、cover_image フィールドを送信しない
        if (this.coverImageFile && this.coverImageFile instanceof File && this.coverImageFile.size > 0) {
          formData.append('cover_image', this.coverImageFile)
          console.log('Adding cover image:', this.coverImageFile.name, this.coverImageFile.size, 'bytes')
        } else {
          console.log('No valid cover image file selected - not sending cover_image field')
          // Ensure we don't send any cover_image data at all
        }
        
        if (this.reportFile && this.reportFile instanceof File && this.reportFile.size > 0) {
          formData.append('file', this.reportFile)
          console.log('Adding report file:', this.reportFile.name, this.reportFile.size, 'bytes')
          console.log('File type:', this.reportFile.type)
          console.log('File last modified:', new Date(this.reportFile.lastModified))
        } else {
          console.log('No valid report file selected - not sending file field')
          console.log('Report file object:', this.reportFile)
          // Ensure we don't send any file data at all
        }
        
        // membership_level は必ず追加
        if (this.formData.membership_level) {
          formData.append('membership_level', this.formData.membership_level)
        }
        
        const endpoint = this.editingReport 
          ? `/api/admin/economic-reports/${this.editingReport.id}`
          : '/api/admin/economic-reports'
        const url = getApiUrl(endpoint)
        
        // Laravelのmultipart PUTは環境によりパースされない場合があるため
        // 編集時もPOSTに統一し、_method=PUT を付与（メソッド擬似）
        const method = 'POST'
        if (this.editingReport) {
          formData.append('_method', 'PUT')
        }
        
        const authToken = apiClient.getCurrentToken()
        const headers = {
          'Accept': 'application/json'
        }
        if (authToken) headers['Authorization'] = authToken.startsWith('Bearer ') ? authToken : `Bearer ${authToken}`

        // Debug: Log form data contents
        console.log('FormData contents:')
        console.log('Editing report:', !!this.editingReport)
        console.log('Cover image file:', this.coverImageFile)
        console.log('Report file:', this.reportFile)
        console.log('Form data cover_image:', this.formData.cover_image)
        console.log('Form data file_url:', this.formData.file_url)
        
        for (let pair of formData.entries()) {
          console.log(pair[0] + ': ' + (pair[1] instanceof File ? pair[1].name + ' (' + pair[1].size + ' bytes)' : pair[1]))
        }

        const response = await fetch(url, {
          method,
          headers,
          body: formData
        })
        
        const data = await response.json()
        console.log('Response status:', response.status)
        console.log('Response:', data)
        
        // Additional debugging for file upload issues
        if (!data.success && data.errors) {
          console.log('Validation errors:', data.errors)
          if (data.errors.file) {
            console.log('File validation errors:', data.errors.file)
          }
        }
        
        if (data.success) {
          this.closeModal()
          await this.loadReports()
          await this.loadStats()
          alert(this.editingReport ? 'レポートを更新しました' : 'レポートを作成しました')
        } else {
          // バリデーション詳細を表示
          if (data.errors) {
            const errorMessages = Object.entries(data.errors).map(([field, messages]) => {
              const fieldName = field === 'cover_image' ? 'カバー画像' :
                               field === 'file' ? 'レポートファイル' :
                               field === 'title' ? 'タイトル' :
                               field === 'description' ? '説明' :
                               field === 'category' ? 'カテゴリ' :
                               field === 'year' ? '年' :
                               field === 'publication_date' ? '公開日' : field
              const msgs = Array.isArray(messages) ? messages : [messages]
              return `${fieldName}: ${msgs.join(', ')}`
            }).join('\n')
            this.error = data.message ? `${data.message}\n\n${errorMessages}` : errorMessages
            alert(this.error)
          } else {
            this.error = data.message || '保存に失敗しました'
            alert(this.error)
          }
        }
      } catch (err) {
        this.error = 'ネットワークエラーが発生しました'
        console.error('Save report error:', err)
      } finally {
        this.saving = false
      }
    },

    async deleteReport(report) {
      if (!confirm(`「${report.title}」を削除しますか？この操作は取り消せません。`)) {
        return
      }
      
      try {
        const authToken2 = apiClient.getCurrentToken()
        const headers2 = { 'Accept': 'application/json' }
        if (authToken2) headers2['Authorization'] = authToken2.startsWith('Bearer ') ? authToken2 : `Bearer ${authToken2}`
        const response = await fetch(getApiUrl(`/api/admin/economic-reports/${report.id}`), {
          method: 'DELETE',
          headers: headers2
        })
        
        const data = await response.json()
        
        if (data.success) {
          await this.loadReports()
          await this.loadStats()
          alert('レポートを削除しました')
        } else {
          alert(data.message || '削除に失敗しました')
        }
      } catch (err) {
        alert('ネットワークエラーが発生しました')
        console.error('Delete report error:', err)
      }
    },

    async togglePublished(report) {
      try {
        const authToken3 = apiClient.getCurrentToken()
        const headers3 = { 'Accept': 'application/json' }
        if (authToken3) headers3['Authorization'] = authToken3.startsWith('Bearer ') ? authToken3 : `Bearer ${authToken3}`
        const response = await fetch(getApiUrl(`/api/admin/economic-reports/${report.id}/toggle-publish`), {
          method: 'PATCH',
          headers: headers3
        })
        
        const data = await response.json()
        
        if (data.success) {
          report.is_published = data.data.is_published
          await this.loadStats()
        } else {
          alert(data.message || '更新に失敗しました')
        }
      } catch (err) {
        alert('ネットワークエラーが発生しました')
        console.error('Toggle published error:', err)
      }
    },

    async toggleFeatured(report) {
      try {
        const authToken4 = apiClient.getCurrentToken()
        const headers4 = { 'Accept': 'application/json' }
        if (authToken4) headers4['Authorization'] = authToken4.startsWith('Bearer ') ? authToken4 : `Bearer ${authToken4}`
        const response = await fetch(getApiUrl(`/api/admin/economic-reports/${report.id}/toggle-feature`), {
          method: 'PATCH',
          headers: headers4
        })
        
        const data = await response.json()
        
        if (data.success) {
          report.is_featured = data.data.is_featured
          await this.loadStats()
        } else {
          alert(data.message || '更新に失敗しました')
        }
      } catch (err) {
        alert('ネットワークエラーが発生しました')
        console.error('Toggle featured error:', err)
      }
    },

    handleCoverImageChange(event) {
      const file = event.target.files[0]
      
      if (file) {
        // Validate file type
        const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif']
        if (!allowedTypes.includes(file.type)) {
          alert('画像ファイルは JPEG, PNG, JPG, GIF 形式のみサポートしています。')
          event.target.value = ''
          this.coverImageFile = null
          this.coverImagePreview = null
          return
        }
        
        // Validate file size (2MB limit)
        if (file.size > 2 * 1024 * 1024) {
          alert('画像ファイルは2MB以下にしてください。')
          event.target.value = ''
          this.coverImageFile = null
          this.coverImagePreview = null
          return
        }
      }
      
      this.coverImageFile = file
      
      // Create preview URL
      if (file) {
        try {
          this.coverImagePreview = URL.createObjectURL(file)
        } catch (e) {
          console.error('Failed to create image preview:', e)
          this.coverImagePreview = null
        }
      } else {
        this.coverImagePreview = null
      }
    },

    handleFileChange(event) {
      const file = event.target.files[0]
      
      if (file) {
        // Validate file type
        const allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']
        const allowedExtensions = ['.pdf', '.doc', '.docx']
        const fileExtension = '.' + file.name.split('.').pop().toLowerCase()
        
        if (!allowedTypes.includes(file.type) && !allowedExtensions.includes(fileExtension)) {
          alert('ファイルは PDF, DOC, DOCX 形式のみサポートしています。')
          event.target.value = ''
          this.reportFile = null
          return
        }
        
        // Validate file size (10MB limit to match backend)
        if (file.size > 10 * 1024 * 1024) {
          alert('ファイルは10MB以下にしてください。')
          event.target.value = ''
          this.reportFile = null
          return
        }
      }
      
      this.reportFile = file
    },

    getImageUrl(imagePath) {
      // Use the centralized URL resolver to avoid issues with API host
      return resolveMediaUrl(imagePath)
    },

    getFileName(filePath) {
      if (!filePath) return 'ファイル名不明'
      
      // Extract filename from path
      const parts = filePath.split('/')
      const filename = parts[parts.length - 1]
      
      // If it's a hashed filename (very long), show a generic name
      if (filename.length > 40) {
        return 'レポートPDFファイル'
      }
      
      return filename
    },

    formatFileSize(bytes) {
      if (!bytes || bytes === 0) return '0 B'
      
      const k = 1024
      const sizes = ['B', 'KB', 'MB', 'GB']
      const i = Math.floor(Math.log(bytes) / Math.log(k))
      
      return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + ' ' + sizes[i]
    },

    getCategoryName(category) {
      const categories = {
        quarterly: '四半期経済レポート',
        annual: '年次経済統計',
        regional: '地域経済調査',
        industry: '産業別統計'
      }
      return categories[category] || category
    },

    formatDate(dateString) {
      if (!dateString) return '-'
      return new Date(dateString).toLocaleDateString('ja-JP')
    },

    formatDateForInput(dateString) {
      if (!dateString) return ''
      return new Date(dateString).toISOString().split('T')[0]
    }
  },
  
  beforeUnmount() {
    // Clean up preview URL when component is destroyed
    if (this.coverImagePreview) {
      try {
        URL.revokeObjectURL(this.coverImagePreview)
      } catch (e) {
        // Ignore errors
      }
    }
  }
}
</script>

<style scoped>
.dashboard {
  padding: 24px;
  background: #f8f9fa;
  min-height: 100vh;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 32px;
}

.page-title {
  font-size: 28px;
  font-weight: 600;
  color: #1a1a1a;
  margin: 0;
}

.add-btn {
  background: #da5761;
  color: white;
  border: none;
  padding: 12px 24px;
  border-radius: 8px;
  font-weight: 500;
  cursor: pointer;
  transition: background-color 0.2s;
}

.add-btn:hover {
  background: #c44854;
}

.search-section {
  background: white;
  padding: 24px;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  margin-bottom: 24px;
}

.search-container {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-bottom: 20px;
}

.search-label {
  font-weight: 500;
  color: #1a1a1a;
  white-space: nowrap;
}

.search-input {
  flex: 1;
  padding: 12px 16px;
  border: 1px solid #e1e5e9;
  border-radius: 8px;
  font-size: 16px;
}

.search-btn {
  background: #da5761;
  color: white;
  border: none;
  padding: 12px;
  border-radius: 8px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
}

.filter-section {
  display: flex;
  gap: 20px;
  flex-wrap: wrap;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.filter-group label {
  font-weight: 500;
  color: #1a1a1a;
  font-size: 14px;
}

.filter-select {
  padding: 8px 12px;
  border: 1px solid #e1e5e9;
  border-radius: 6px;
  font-size: 14px;
  min-width: 150px;
}

.table-container {
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  overflow: hidden;
  margin-bottom: 24px;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
}

.data-table th {
  background: #f8f9fa;
  padding: 16px;
  text-align: left;
  font-weight: 600;
  color: #1a1a1a;
  border-bottom: 1px solid #e1e5e9;
}

.data-table td {
  padding: 16px;
  border-bottom: 1px solid #f1f3f5;
  vertical-align: top;
}

.title-cell {
  max-width: 250px;
}

.title-content {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.title {
  font-weight: 500;
  color: #1a1a1a;
  line-height: 1.4;
}

.pages {
  font-size: 12px;
  color: #6c757d;
}

.category-badge {
  display: inline-block;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 500;
  color: white;
}

.category-quarterly { background: #da5761; color: white; }
.category-annual { background: #1A1A1A; color: white; }
.category-regional { background: #c44853; color: white; }
.category-industry { background: #555; color: white; }

.status-badge {
  display: inline-block;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 500;
  cursor: pointer;
  transition: opacity 0.2s;
}

.status-badge:hover {
  opacity: 0.8;
}

.published {
  background: #d4edda;
  color: #155724;
}

.draft {
  background: #f8d7da;
  color: #721c24;
}

.featured {
  background: #fff3cd;
  color: #856404;
}

.not-featured {
  background: #e9ecef;
  color: #6c757d;
}

.actions {
  display: flex;
  gap: 8px;
}

.action-btn {
  padding: 6px 12px;
  border: none;
  border-radius: 4px;
  font-size: 12px;
  cursor: pointer;
  transition: background-color 0.2s;
}

.edit {
  background: #e3f2fd;
  color: #1976d2;
}

.edit:hover {
  background: #bbdefb;
}

.delete {
  background: #ffebee;
  color: #d32f2f;
}

.delete:hover {
  background: #ffcdd2;
}

.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 16px;
  padding: 20px;
}

.pagination-btn {
  padding: 8px 16px;
  border: 1px solid #e1e5e9;
  background: white;
  border-radius: 6px;
  cursor: pointer;
  transition: background-color 0.2s;
}

.pagination-btn:hover:not(:disabled) {
  background: #f8f9fa;
}

.pagination-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.pagination-info {
  font-weight: 500;
  color: #1a1a1a;
}

.stats-section {
  background: white;
  padding: 24px;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 20px;
}

.stat-card {
  text-align: center;
  padding: 20px;
  background: #f8f9fa;
  border-radius: 8px;
}

.stat-number {
  font-size: 32px;
  font-weight: 700;
  color: #da5761;
  margin-bottom: 8px;
}

.stat-label {
  font-size: 14px;
  color: #6c757d;
  font-weight: 500;
}

.loading, .error {
  text-align: center;
  padding: 40px;
  color: #6c757d;
}

.error {
  color: #dc3545;
}

/* モーダルスタイル */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-content {
  background: white;
  border-radius: 12px;
  width: 90%;
  max-width: 800px;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 24px 24px 0;
  border-bottom: 1px solid #e1e5e9;
  padding-bottom: 16px;
  margin-bottom: 24px;
}

.modal-header h2 {
  margin: 0;
  font-size: 24px;
  font-weight: 600;
  color: #1a1a1a;
}

.modal-close {
  background: none;
  border: none;
  font-size: 28px;
  cursor: pointer;
  color: #6c757d;
  padding: 0;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.modal-form {
  padding: 0 24px 24px;
}

.form-row {
  display: flex;
  gap: 20px;
  margin-bottom: 20px;
}

.form-group {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.form-group.half {
  flex: 0 0 calc(50% - 10px);
}

.form-group.full {
  flex: 1 1 100%;
}

.form-group label {
  font-weight: 500;
  color: #1a1a1a;
  font-size: 14px;
}

.required {
  color: #dc3545;
}

.form-input, .form-select, .form-textarea {
  padding: 12px 16px;
  border: 1px solid #e1e5e9;
  border-radius: 8px;
  font-size: 16px;
  transition: border-color 0.2s;
}

.form-input:focus, .form-select:focus, .form-textarea:focus {
  outline: none;
  border-color: #da5761;
}

.form-file {
  display: none;
}

.file-upload-wrapper {
  margin-top: 12px;
}

.file-upload-btn {
  display: inline-block;
  padding: 10px 20px;
  background: #f8f9fa;
  border: 2px dashed #d1d5db;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s;
  font-size: 14px;
  color: #1a1a1a;
  font-weight: 500;
  text-align: center;
}

.file-upload-btn:hover {
  background: #fef3f2;
  border-color: #da5761;
  color: #da5761;
}

/* File Upload Row Styles */
.file-upload-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 24px;
  margin-bottom: 24px;
}

/* Image Preview Styles */
.file-preview {
  margin-bottom: 12px;
  padding: 12px;
  background: #f8f9fa;
  border-radius: 8px;
  border: 1px solid #e1e5e9;
}

.preview-image-container {
  display: flex;
  flex-direction: column;
  gap: 12px;
  align-items: center;
}

.preview-image {
  width: 100%;
  max-width: 200px;
  height: 120px;
  object-fit: cover;
  border-radius: 6px;
  border: 1px solid #e1e5e9;
}

.preview-info {
  text-align: center;
  display: flex;
  flex-direction: column;
  gap: 4px;
  width: 100%;
}

.preview-label {
  font-size: 14px;
  font-weight: 500;
  color: #1a1a1a;
}

.preview-detail {
  font-size: 12px;
  color: #6c757d;
  word-break: break-word;
}

/* File Info Card Styles */
.file-info-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
  padding: 16px;
  background: #f8f9fa;
  border-radius: 8px;
  border: 1px solid #e1e5e9;
  margin-bottom: 12px;
  text-align: center;
}

.file-info-card.new-file {
  background: #ecfdf5;
  border-color: #6ee7b7;
}

.file-icon {
  width: 48px;
  height: 48px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: white;
  border-radius: 8px;
  color: #da5761;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.file-details {
  width: 100%;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.file-name {
  font-size: 14px;
  font-weight: 500;
  color: #1a1a1a;
  word-break: break-word;
  line-height: 1.4;
}

.file-meta {
  font-size: 12px;
  color: #6c757d;
  line-height: 1.3;
}

.form-help {
  font-size: 12px;
  color: #6c757d;
  margin-top: 8px;
}

.checkbox-group {
  display: flex;
  gap: 20px;
  flex-wrap: wrap;
  justify-content: flex-start;
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
  cursor: pointer;
}

.checkbox-label input[type="checkbox"] {
  margin: 0;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  padding-top: 24px;
  border-top: 1px solid #e1e5e9;
  margin-top: 24px;
}

.btn-cancel {
  padding: 12px 24px;
  border: 1px solid #e1e5e9;
  background: white;
  color: #6c757d;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 500;
  transition: background-color 0.2s;
}

.btn-cancel:hover {
  background: #f8f9fa;
}

.btn-save {
  padding: 12px 24px;
  border: none;
  background: #da5761;
  color: white;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 500;
  transition: background-color 0.2s;
}

.btn-save:hover:not(:disabled) {
  background: #c44854;
}

.btn-save:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

@media (max-width: 768px) {
  .form-row {
    flex-direction: column;
  }
  
  .form-group.half {
    flex: 1;
  }
  
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .filter-section {
    flex-direction: column;
  }
  
  .checkbox-group {
    flex-direction: column;
    gap: 12px;
  }
  
  /* File upload responsive styles */
  .file-upload-row {
    grid-template-columns: 1fr;
    gap: 16px;
  }
  
  .preview-image {
    max-width: 150px;
    height: 100px;
  }
  
  .file-info-card {
    padding: 12px;
  }
  
  .file-icon {
    width: 40px;
    height: 40px;
  }
}
</style>
