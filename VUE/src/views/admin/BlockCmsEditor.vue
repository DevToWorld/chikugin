<template>
  <AdminLayout>
    <div class="cms">
      <div class="pane left">
        <div class="toolbar">
          <input v-model="search" class="input" placeholder="ページ検索" @keyup.enter="loadPages" />
          <button class="btn" @click="openCreate">新規</button>
        </div>
        <div class="list">
          <div v-for="p in visiblePages" :key="p.id" :class="['item',{active: currentPage && currentPage.id===p.id}]" @click="selectPage(p)">
            <div class="title">{{ p.title }}</div>
            <div class="slug">/{{ p.slug }}</div>
          </div>
        </div>
      </div>
      <div class="pane center">
        <div v-if="currentPage" class="editor-form">
          <div class="field">
            <label>ページタイトル</label>
            <input v-model="currentPage.title" class="input" @change="savePageMeta" />
          </div>

          <div class="field">
            <label>ヒーロー画像（hero）</label>
            <div class="page-image-row">
              <div class="img-preview"><img :src="getImageUrlByKey('hero') || ''" alt="preview"/></div>
              <div class="img-meta">
                <div class="img-key">images.hero</div>
                <div class="img-actions">
                  <button class="btn" @click="uploadForKey('hero')">アップロードファイル</button>
                </div>
              </div>
            </div>
            <div class="help">推奨比率 16:9（md/lgプリセットで自動リサイズ配信）。</div>
          </div>

          <!-- Layout view toggle (for key pages like company/aboutus/about/consult/membership) -->
          <div class="field" v-if="currentPage && (['company','aboutus','about','consult','membership','services','membership-application','seminar-application','contact','navigation','footer','economic','publications','faq','glossary','sitemap'].some(k => (currentPage.slug||'').toLowerCase().includes(k)))">
            <label>編集モード</label>
            <div style="display:flex; gap:10px; align-items:center;">
              <label style="display:flex; gap:6px; align-items:center;">
                <input type="checkbox" v-model="layoutMode" /> ページ構成ビューで編集（実ページに近い配置）
              </label>
            </div>
          </div>

          <template v-if="showContentEditor">
            <div class="section-title">コンテンツ</div>
            <div class="field">
              <label>エディター</label>
              <textarea v-model="richText.html" class="textarea" rows="18" @change="saveRich"></textarea>
            </div>
            <div class="field">
              <label>本文用画像</label>
              <div style="display:flex; gap:8px; align-items:center;">
                <input ref="contentImgInput" type="file" accept="image/*" style="display:none" @change="onContentImageSelected" />
                <button class="btn" @click="selectContentImage">画像を選択</button>
                <button class="btn" @click="insertLastContentImage" :disabled="!lastContentImgUrl">本文に画像を追加</button>
                <span class="help" v-if="lastContentImgUrl">準備済み: {{ lastContentImgUrl }}</span>
              </div>
            </div>
          </template>
          
          <!-- membership: 構成ビュー（layoutMode） -->
          <template v-if="currentPage && (currentPage.slug==='membership' || currentPage.slug==='services') && layoutMode">
            <div class="section-title">導入（Introduction）</div>
            <div class="field"><label>見出し</label><input v-model="membershipTexts.intro_title" class="input" placeholder="ご入会に際しまして" /></div>
            <div class="field"><label>本文</label><textarea v-model="membershipTexts.intro_text" class="textarea" rows="4"></textarea></div>

            <div class="section-title">サービス（Services）</div>
            <div class="field"><label>見出し</label><input v-model="membershipTexts.services_title" class="input" placeholder="会員が受けられる主なサービス内容" /></div>
            <div class="field"><label>英字</label><input v-model="membershipTexts.services_label" class="input" placeholder="service" /></div>

            <div class="field"><label>プレミアサービス（見出し）</label><input v-model="membershipTexts.premium_category_title" class="input" /></div>
            <div class="field"><label>プレミア項目1（タグ）</label><input v-model="membershipTexts.premium_service1_tag" class="input" /></div>
            <div class="field"><label>プレミア項目1（名称）</label><input v-model="membershipTexts.premium_service1_name" class="input" /></div>

            <div class="field"><label>スタンダード＆プレミア（見出し）</label><input v-model="membershipTexts.standard_premium_category_title" class="input" /></div>
            <div class="field"><label>項目1（タグ）</label><input v-model="membershipTexts.standard_service1_tag" class="input" /></div>
            <div class="field"><label>項目1（名称）</label><input v-model="membershipTexts.standard_service1_name" class="input" /></div>
            <div class="field"><label>項目2（タグ）</label><input v-model="membershipTexts.standard_service2_tag" class="input" /></div>
            <div class="field"><label>項目2（名称）</label><input v-model="membershipTexts.standard_service2_name" class="input" /></div>
            <div class="field"><label>項目3（タグ）</label><input v-model="membershipTexts.standard_service3_tag" class="input" /></div>
            <div class="field"><label>項目3（名称）</label><input v-model="membershipTexts.standard_service3_name" class="input" /></div>
            <div class="field"><label>項目4（タグ）</label><input v-model="membershipTexts.standard_service4_tag" class="input" /></div>
            <div class="field"><label>項目4（名称）</label><input v-model="membershipTexts.standard_service4_name" class="input" /></div>
            <div class="field"><label>項目5（タグ）</label><input v-model="membershipTexts.standard_service5_tag" class="input" /></div>
            <div class="field"><label>項目5（名称）</label><input v-model="membershipTexts.standard_service5_name" class="input" /></div>

            <div class="section-title">会員サービスについて</div>
            <div class="field"><label>見出し</label><input v-model="membershipTexts.membership_info_title" class="input" /></div>
            <div class="field"><label>会員のメリット（ラベル）</label><input v-model="membershipTexts.membership_benefits_label" class="input" /></div>
            <div class="field"><label>会員のメリット（本文）</label><textarea v-model="membershipTexts.membership_benefits_text" class="textarea" rows="3"></textarea></div>
            <div class="field"><label>月会費（ラベル）</label><input v-model="membershipTexts.membership_fee_label" class="input" /></div>
            <div class="field"><label>月会費（本文）</label><textarea v-model="membershipTexts.membership_fee_text" class="textarea" rows="3"></textarea></div>

            <div class="section-title">入会までの流れ（Flow）</div>
            <div class="field"><label>見出し</label><input v-model="membershipTexts.flow_title" class="input" /></div>
            <div class="field"><label>英字</label><input v-model="membershipTexts.flow_label" class="input" /></div>
            <div class="field"><label>STEP1（見出し）</label><input v-model="membershipTexts.step1_title" class="input" /></div>
            <div class="field"><label>STEP1（説明）</label><textarea v-model="membershipTexts.step1_desc" class="textarea" rows="3"></textarea></div>
            <div class="field"><label>STEP2（見出し）</label><input v-model="membershipTexts.step2_title" class="input" /></div>
            <div class="field"><label>STEP2（説明）</label><textarea v-model="membershipTexts.step2_desc" class="textarea" rows="3"></textarea></div>
            <div class="field"><label>STEP3（見出し）</label><input v-model="membershipTexts.step3_title" class="input" /></div>
            <div class="field"><label>STEP4（見出し）</label><input v-model="membershipTexts.step4_title" class="input" /></div>

            <div class="section-title">CTA ボタン</div>
            <div class="field"><label>お問い合わせ（ボタン文言）</label><input v-model="membershipTexts.cta_primary" class="input" /></div>
            <div class="field"><label>入会（ボタン文言）</label><input v-model="membershipTexts.cta_secondary" class="input" /></div>

            <!-- 画像（レイアウト近似配置） -->
            <div class="section-title">画像（レイアウト）</div>
            <div class="field">
              <label>プレミア項目1（premium_service1_image）</label>
              <div class="page-image-row">
                <div class="img-preview"><img :src="getImageUrlByKey('premium_service1_image') || ''" alt="preview"/></div>
                <div class="img-meta">
                  <div class="img-key">images.premium_service1_image</div>
                  <div class="img-actions">
                    <input ref="img_premium_service1_image" type="file" accept="image/*" style="display:none" @change="onCompanyImageSelected('premium_service1_image', $event)" />
                    <button class="btn" @click="triggerCompanyImageUpload('premium_service1_image')">アップロードファイル</button>
                  </div>
                </div>
              </div>
            </div>
            <div class="field">
              <label>スタンダード項目1（standard_service1_image）</label>
              <div class="page-image-row">
                <div class="img-preview"><img :src="getImageUrlByKey('standard_service1_image') || ''" alt="preview"/></div>
                <div class="img-meta">
                  <div class="img-key">images.standard_service1_image</div>
                  <div class="img-actions">
                    <input ref="img_standard_service1_image" type="file" accept="image/*" style="display:none" @change="onCompanyImageSelected('standard_service1_image', $event)" />
                    <button class="btn" @click="triggerCompanyImageUpload('standard_service1_image')">アップロードファイル</button>
                  </div>
                </div>
              </div>
            </div>
            <div class="field">
              <label>スタンダード項目2（standard_service2_image）</label>
              <div class="page-image-row">
                <div class="img-preview"><img :src="getImageUrlByKey('standard_service2_image') || ''" alt="preview"/></div>
                <div class="img-meta">
                  <div class="img-key">images.standard_service2_image</div>
                  <div class="img-actions">
                    <input ref="img_standard_service2_image" type="file" accept="image/*" style="display:none" @change="onCompanyImageSelected('standard_service2_image', $event)" />
                    <button class="btn" @click="triggerCompanyImageUpload('standard_service2_image')">アップロードファイル</button>
                  </div>
                </div>
              </div>
            </div>
            <div class="field">
              <label>スタンダード項目3（standard_service3_image）</label>
              <div class="page-image-row">
                <div class="img-preview"><img :src="getImageUrlByKey('standard_service3_image') || ''" alt="preview"/></div>
                <div class="img-meta">
                  <div class="img-key">images.standard_service3_image</div>
                  <div class="img-actions">
                    <input ref="img_standard_service3_image" type="file" accept="image/*" style="display:none" @change="onCompanyImageSelected('standard_service3_image', $event)" />
                    <button class="btn" @click="triggerCompanyImageUpload('standard_service3_image')">アップロードファイル</button>
                  </div>
                </div>
              </div>
            </div>
            <div class="field">
              <label>スタンダード項目4（standard_service4_image）</label>
              <div class="page-image-row">
                <div class="img-preview"><img :src="getImageUrlByKey('standard_service4_image') || ''" alt="preview"/></div>
                <div class="img-meta">
                  <div class="img-key">images.standard_service4_image</div>
                  <div class="img-actions">
                    <input ref="img_standard_service4_image" type="file" accept="image/*" style="display:none" @change="onCompanyImageSelected('standard_service4_image', $event)" />
                    <button class="btn" @click="triggerCompanyImageUpload('standard_service4_image')">アップロードファイル</button>
                  </div>
                </div>
              </div>
            </div>
            <div class="field">
              <label>スタンダード項目5（standard_service5_image）</label>
              <div class="page-image-row">
                <div class="img-preview"><img :src="getImageUrlByKey('standard_service5_image') || ''" alt="preview"/></div>
                <div class="img-meta">
                  <div class="img-key">images.standard_service5_image</div>
                  <div class="img-actions">
                    <input ref="img_standard_service5_image" type="file" accept="image/*" style="display:none" @change="onCompanyImageSelected('standard_service5_image', $event)" />
                    <button class="btn" @click="triggerCompanyImageUpload('standard_service5_image')">アップロードファイル</button>
                  </div>
                </div>
              </div>
            </div>

            
          </template>

          <!-- membership: 非レイアウト（一覧） -->
          <template v-if="currentPage && (currentPage.slug==='membership' || currentPage.slug==='services') && !layoutMode">
            <div class="field" v-for="(val, key) in membershipTexts" :key="`membership-${key}`">
              <label>{{ displayLabel(key) }}</label>
              <input v-model="membershipTexts[key]" class="input" />
            </div>
          </template>

          <!-- standard-membership: 構成ビュー（layoutMode） -->
          <template v-if="currentPage && currentPage.slug==='standard-membership' && layoutMode">
            <div class="section-title">導入（Introduction）</div>
            <div class="field"><label>見出し</label><input v-model="standardTexts.intro_title" class="input" /></div>
            <div class="field"><label>本文</label><textarea v-model="standardTexts.intro_text" class="textarea" rows="4"></textarea></div>

            <div class="section-title">サービス（Services）</div>
            <div class="field"><label>見出し</label><input v-model="standardTexts.services_title" class="input" /></div>
            <div class="field"><label>英字</label><input v-model="standardTexts.services_label" class="input" /></div>
            <div class="field"><label>スタンダードサービス（見出し）</label><input v-model="standardTexts.standard_category_title" class="input" /></div>

            <!-- 画像（レイアウト近似配置） -->
            <div class="section-title">画像（レイアウト）</div>
            <div class="field">
              <label>項目1（standard_service1_image）</label>
              <div class="page-image-row">
                <div class="img-preview"><img :src="getImageUrlByKey('standard_service1_image') || ''" alt="preview"/></div>
                <div class="img-meta">
                  <div class="img-actions">
                    <input ref="img_std_service1_image" type="file" accept="image/*" style="display:none" @change="onCompanyImageSelected('standard_service1_image', $event)" />
                    <button class="btn" @click="triggerCompanyImageUpload('standard_service1_image')">アップロードファイル</button>
                  </div>
                </div>
              </div>
            </div>
            <div class="field">
              <label>項目2（standard_service2_image）</label>
              <div class="page-image-row">
                <div class="img-preview"><img :src="getImageUrlByKey('standard_service2_image') || ''" alt="preview"/></div>
                <div class="img-meta">
                  <div class="img-actions">
                    <input ref="img_std_service2_image" type="file" accept="image/*" style="display:none" @change="onCompanyImageSelected('standard_service2_image', $event)" />
                    <button class="btn" @click="triggerCompanyImageUpload('standard_service2_image')">アップロードファイル</button>
                  </div>
                </div>
              </div>
            </div>
            <div class="field">
              <label>項目3（standard_service3_image）</label>
              <div class="page-image-row">
                <div class="img-preview"><img :src="getImageUrlByKey('standard_service3_image') || ''" alt="preview"/></div>
                <div class="img-meta">
                  <div class="img-actions">
                    <input ref="img_std_service3_image" type="file" accept="image/*" style="display:none" @change="onCompanyImageSelected('standard_service3_image', $event)" />
                    <button class="btn" @click="triggerCompanyImageUpload('standard_service3_image')">アップロードファイル</button>
                  </div>
                </div>
              </div>
            </div>
            <div class="field">
              <label>項目4（standard_service4_image）</label>
              <div class="page-image-row">
                <div class="img-preview"><img :src="getImageUrlByKey('standard_service4_image') || ''" alt="preview"/></div>
                <div class="img-meta">
                  <div class="img-actions">
                    <input ref="img_std_service4_image" type="file" accept="image/*" style="display:none" @change="onCompanyImageSelected('standard_service4_image', $event)" />
                    <button class="btn" @click="triggerCompanyImageUpload('standard_service4_image')">アップロードファイル</button>
                  </div>
                </div>
              </div>
            </div>
            <div class="field">
              <label>項目5（standard_service5_image）</label>
              <div class="page-image-row">
                <div class="img-preview"><img :src="getImageUrlByKey('standard_service5_image') || ''" alt="preview"/></div>
                <div class="img-meta">
                  <div class="img-actions">
                    <input ref="img_std_service5_image" type="file" accept="image/*" style="display:none" @change="onCompanyImageSelected('standard_service5_image', $event)" />
                    <button class="btn" @click="triggerCompanyImageUpload('standard_service5_image')">アップロードファイル</button>
                  </div>
                </div>
              </div>
            </div>
            <div class="field"><label>項目1（タグ）</label><input v-model="standardTexts.standard_service1_tag" class="input" /></div>
            <div class="field"><label>項目1（名称）</label><input v-model="standardTexts.standard_service1_name" class="input" /></div>
            <div class="field"><label>項目2（タグ）</label><input v-model="standardTexts.standard_service2_tag" class="input" /></div>
            <div class="field"><label>項目2（名称）</label><input v-model="standardTexts.standard_service2_name" class="input" /></div>
            <div class="field"><label>項目3（タグ）</label><input v-model="standardTexts.standard_service3_tag" class="input" /></div>
            <div class="field"><label>項目3（名称）</label><input v-model="standardTexts.standard_service3_name" class="input" /></div>
            <div class="field"><label>項目4（タグ）</label><input v-model="standardTexts.standard_service4_tag" class="input" /></div>
            <div class="field"><label>項目4（名称）</label><input v-model="standardTexts.standard_service4_name" class="input" /></div>
            <div class="field"><label>項目5（タグ）</label><input v-model="standardTexts.standard_service5_tag" class="input" /></div>
            <div class="field"><label>項目5（名称）</label><input v-model="standardTexts.standard_service5_name" class="input" /></div>

            <div class="section-title">会員サービスについて</div>
            <div class="field"><label>見出し</label><input v-model="standardTexts.membership_info_title" class="input" /></div>
            <div class="field"><label>会員のメリット（ラベル）</label><input v-model="standardTexts.membership_benefits_label" class="input" /></div>
            <div class="field"><label>会員のメリット（本文）</label><textarea v-model="standardTexts.membership_benefits_text" class="textarea" rows="3"></textarea></div>
            <div class="field"><label>月会費（ラベル）</label><input v-model="standardTexts.membership_fee_label" class="input" /></div>
            <div class="field"><label>月会費（本文）</label><textarea v-model="standardTexts.membership_fee_text" class="textarea" rows="3"></textarea></div>

            <div class="section-title">入会までの流れ（Flow）</div>
            <div class="field"><label>見出し</label><input v-model="standardTexts.flow_title" class="input" /></div>
            <div class="field"><label>英字</label><input v-model="standardTexts.flow_label" class="input" /></div>
            <div class="field"><label>STEP1（見出し）</label><input v-model="standardTexts.step1_title" class="input" /></div>
            <div class="field"><label>STEP1（説明）</label><textarea v-model="standardTexts.step1_desc" class="textarea" rows="3"></textarea></div>
            <div class="field"><label>STEP2（見出し）</label><input v-model="standardTexts.step2_title" class="input" /></div>
            <div class="field"><label>STEP2（説明）</label><textarea v-model="standardTexts.step2_desc" class="textarea" rows="3"></textarea></div>
            <div class="field"><label>STEP3（見出し）</label><input v-model="standardTexts.step3_title" class="input" /></div>
            <div class="field"><label>STEP4（見出し）</label><input v-model="standardTexts.step4_title" class="input" /></div>

            <div class="section-title">CTA ボタン</div>
            <div class="field"><label>お問い合わせ（ボタン文言）</label><input v-model="standardTexts.cta_primary" class="input" /></div>
            <div class="field"><label>入会（ボタン文言）</label><input v-model="standardTexts.cta_secondary" class="input" /></div>

            
          </template>

          <!-- premium-membership: 構成ビュー（layoutMode） -->
          <template v-if="currentPage && currentPage.slug==='premium-membership' && layoutMode">
            <div class="section-title">話題の特典（Benefits）</div>
            <div class="field"><label>見出し</label><input v-model="premiumTexts.benefits_title" class="input" /></div>
            <div class="field"><label>英字</label><input v-model="premiumTexts.benefits_label" class="input" /></div>

            <div class="section-title">特集（Featured）</div>
            <div class="field"><label>見出し</label><input v-model="premiumTexts.featured_title" class="input" /></div>
            <div class="field"><label>本文</label><textarea v-model="premiumTexts.featured_body" class="textarea" rows="4"></textarea></div>
            <div class="field"><label>ボタン文言</label><input v-model="premiumTexts.featured_cta" class="input" /></div>

            <div class="section-title">CTA ボタン</div>
            <div class="field"><label>お問い合わせ（ボタン文言）</label><input v-model="premiumTexts.cta_primary" class="input" /></div>
            <div class="field"><label>入会（ボタン文言）</label><input v-model="premiumTexts.cta_secondary" class="input" /></div>

            <!-- 画像（レイアウト近似配置） -->
            <div class="section-title">特集画像（Featured）</div>
            <div class="field">
              <label>特集画像（featured_image）</label>
              <div class="page-image-row">
                <div class="img-preview"><img :src="getImageUrlByKey('featured_image') || ''" alt="preview"/></div>
                <div class="img-meta">
                  <div class="img-key">images.featured_image</div>
                  <div class="img-actions">
                    <input ref="img_featured_image" type="file" accept="image/*" style="display:none" @change="onCompanyImageSelected('featured_image', $event)" />
                    <button class="btn" @click="triggerCompanyImageUpload('featured_image')">アップロードファイル</button>
                  </div>
                </div>
              </div>
            </div>
            
          </template>

          <!-- contact: シンプル構成ビュー（layoutMode） -->
          <template v-if="currentPage && (currentPage.slug==='contact' || currentPage.slug.startsWith('contact-')) && layoutMode">
            <div class="section-title">お問い合わせ（Contact）</div>

            <div class="field" v-for="key in contactBasicKeys" :key="`contact-basic-${key}`">
              <label>{{ displayLabel(key) }}</label>
              <input v-model="contactTexts[key]" class="input" />
            </div>

            <div class="section-title">件名の選択肢</div>
            <div class="field" v-for="key in contactOptionKeys" :key="`contact-option-${key}`">
              <label>{{ displayLabel(key) }}</label>
              <input v-model="contactTexts[key]" class="input" />
            </div>

            <div class="section-title">フォーム項目ラベル</div>
            <div class="field" v-for="key in contactFieldLabelKeys" :key="`contact-label-${key}`">
              <label>{{ displayLabel(key) }}</label>
              <input v-model="contactTexts[key]" class="input" />
            </div>

          <div class="section-title">規約案内</div>
          <div class="field" v-for="key in contactTextareaKeys" :key="`contact-textarea-${key}`">
            <label>{{ displayLabel(key) }}</label>
            <textarea v-model="contactTexts[key]" class="textarea" rows="4"></textarea>
          </div>
          <div class="field" v-for="key in contactAgreementKeys" :key="`contact-agree-${key}`">
            <label>{{ displayLabel(key) }}</label>
            <input v-model="contactTexts[key]" class="input" />
          </div>

            <div class="section-title">規約テキスト（Contact専用）</div>
            <div class="field"><label>第1条（見出し）</label><input v-model="contactTexts.article1_title" class="input" placeholder="第1条(目的）" /></div>
            <div class="field"><label>第1条（本文・テキスト）</label><textarea v-model="contactTexts.article1_body" class="textarea" rows="4"></textarea></div>
            <div class="field"><label>第2条（見出し）</label><input v-model="contactTexts.article2_title" class="input" placeholder="第2条(会員）" /></div>
            <div class="field"><label>第2条（本文・HTML）</label><textarea v-model="contactHtmls.article2_body" class="textarea" rows="6"></textarea></div>
            <div class="field"><label>第3条（見出し）</label><input v-model="contactTexts.article3_title" class="input" placeholder="第3条(会員種別および会員サービス）" /></div>
            <div class="field"><label>第3条（導入テキスト）</label><textarea v-model="contactTexts.article3_body" class="textarea" rows="3"></textarea></div>

            <div class="section-title">会員サービス（第3条・リスト）</div>
            <div class="field"><label>スタンダード会員（見出し）</label><input v-model="contactTexts.standard_title" class="input" /></div>
            <div class="field"><label>スタンダード①</label><input v-model="contactTexts.standard_item1" class="input" /></div>
            <div class="field"><label>スタンダード②</label><input v-model="contactTexts.standard_item2" class="input" /></div>
            <div class="field"><label>スタンダード③</label><input v-model="contactTexts.standard_item3" class="input" /></div>
            <div class="field"><label>スタンダード④</label><input v-model="contactTexts.standard_item4" class="input" /></div>
            <div class="field"><label>プレミアムネット会員（見出し）</label><input v-model="contactTexts.premium_title" class="input" /></div>
            <div class="field"><label>プレミアム（導入）</label><input v-model="contactTexts.premium_intro" class="input" /></div>
            <div class="field"><label>プレミアム⑤</label><input v-model="contactTexts.premium_item5" class="input" /></div>
            <div class="field"><label>プレミアム⑥</label><input v-model="contactTexts.premium_item6" class="input" /></div>
            <div class="field"><label>プレミアム⑦</label><input v-model="contactTexts.premium_item7" class="input" /></div>
            <div class="field"><label>規約PDFリンク文言</label><input v-model="contactTexts.terms_download_label" class="input" placeholder="会員規約をPDFでダウンロード" /></div>

            <div class="section-title">連絡先ブロック</div>
            <div class="field" v-for="key in contactInfoKeys" :key="`contact-info-${key}`">
              <label>{{ displayLabel(key) }}</label>
              <input v-model="contactTexts[key]" class="input" />
            </div>

            <!-- 画像（レイアウト近似配置）: お問い合わせセクション背景 -->
            <div class="section-title">背景画像</div>
            <div class="field">
              <label>お問い合わせセクション背景（contact_section_bg）</label>
              <div class="page-image-row">
                <div class="img-preview"><img :src="getImageUrlByKey('contact_section_bg') || ''" alt="preview"/></div>
                <div class="img-meta">
                  <div class="img-key">images.contact_section_bg</div>
                  <div class="img-actions">
                    <input ref="img_contact_section_bg" type="file" accept="image/*" style="display:none" @change="onCompanyImageSelected('contact_section_bg', $event)" />
                    <button class="btn" @click="triggerCompanyImageUpload('contact_section_bg')">アップロードファイル</button>
                  </div>
                </div>
              </div>
            </div>
          </template>

          <template v-if="currentPage && (currentPage.slug==='membership-application' || currentPage.slug.startsWith('membership-application-')) && layoutMode">
            <div class="section-title">会員申込フォーム</div>
            <div class="field" v-for="key in membershipApplicationBasicKeys" :key="`membership-app-basic-${key}`">
              <label>{{ displayLabel(key) }}</label>
              <input v-model="membershipApplicationTexts[key]" class="input" />
            </div>

            <div class="section-title">件名の選択肢</div>
            <div class="field" v-for="key in membershipApplicationOptionKeys" :key="`membership-app-option-${key}`">
              <label>{{ displayLabel(key) }}</label>
              <input v-model="membershipApplicationTexts[key]" class="input" />
            </div>

            <div class="section-title">フォーム項目ラベル</div>
            <div class="field" v-for="key in membershipApplicationFieldLabelKeys" :key="`membership-app-label-${key}`">
              <label>{{ displayLabel(key) }}</label>
              <input v-model="membershipApplicationTexts[key]" class="input" />
            </div>

            <div class="section-title">規約セクション（見出し）</div>
            <div class="field" v-for="key in membershipApplicationHeadingKeys" :key="`membership-app-heading-${key}`">
              <label>{{ displayLabel(key) }}</label>
              <input v-model="membershipApplicationTexts[key]" class="input" />
            </div>

            <div class="section-title">規約本文・説明</div>
            <div class="field" v-for="key in membershipApplicationTextareaKeys" :key="`membership-app-textarea-${key}`">
              <label>{{ displayLabel(key) }}</label>
              <textarea v-model="membershipApplicationTexts[key]" class="textarea" rows="4"></textarea>
            </div>

            <div class="section-title">規約同意・ボタン</div>
            <div class="field" v-for="key in membershipApplicationAgreementKeys" :key="`membership-app-agree-${key}`">
              <label>{{ displayLabel(key) }}</label>
              <input v-model="membershipApplicationTexts[key]" class="input" />
            </div>

            <!-- 共通ヒーロー（メディアレジストリ：hero_membership） -->
            <div class="section-title">ページ上部ヒーロー（共通）</div>
            <div class="field">
              <label>ヒーロー画像（hero_membership）</label>
              <div class="page-image-row">
                <div class="img-preview"><img :src="getRegistryImageUrl('hero_membership') || ''" alt="preview"/></div>
                <div class="img-meta">
                  <div class="img-key">media.hero_membership</div>
                  <div class="img-actions">
                    <input ref="img_registry_hero_membership" type="file" accept="image/*" style="display:none" @change="onReplaceRegistryImage('hero_membership', $event)" />
                    <button class="btn" @click="triggerRegistryUpload('hero_membership')">アップロードファイル</button>
                  </div>
                </div>
              </div>
              <div class="help">確認/完了ページ等でも共通で使用されます。</div>
            </div>
          </template>

          <!-- home: レイアウト近似編集（layoutMode） -->
          <template v-if="currentPage && currentPage.slug==='home' && layoutMode">
            <div class="section-title">研究所についてカード画像</div>
            <div class="field">
              <label>カード1（about_card1_image）</label>
              <div class="page-image-row">
                <div class="img-preview"><img :src="getImageUrlByKey('about_card1_image') || ''" alt="preview"/></div>
                <div class="img-meta">
                  <div class="img-key">images.about_card1_image</div>
                  <div class="img-actions">
                    <input ref="img_about_card1_image" type="file" accept="image/*" style="display:none" @change="onCompanyImageSelected('about_card1_image', $event)" />
                    <button class="btn" @click="triggerCompanyImageUpload('about_card1_image')">アップロードファイル</button>
                  </div>
                </div>
              </div>
            </div>
            <div class="field">
              <label>カード2（about_card2_image）</label>
              <div class="page-image-row">
                <div class="img-preview"><img :src="getImageUrlByKey('about_card2_image') || ''" alt="preview"/></div>
                <div class="img-meta">
                  <div class="img-key">images.about_card2_image</div>
                  <div class="img-actions">
                    <input ref="img_about_card2_image" type="file" accept="image/*" style="display:none" @change="onCompanyImageSelected('about_card2_image', $event)" />
                    <button class="btn" @click="triggerCompanyImageUpload('about_card2_image')">アップロードファイル</button>
                  </div>
                </div>
              </div>
            </div>
            <div class="field">
              <label>カード3（about_card3_image）</label>
              <div class="page-image-row">
                <div class="img-preview"><img :src="getImageUrlByKey('about_card3_image') || ''" alt="preview"/></div>
                <div class="img-meta">
                  <div class="img-key">images.about_card3_image</div>
                  <div class="img-actions">
                    <input ref="img_about_card3_image" type="file" accept="image/*" style="display:none" @change="onCompanyImageSelected('about_card3_image', $event)" />
                    <button class="btn" @click="triggerCompanyImageUpload('about_card3_image')">アップロードファイル</button>
                  </div>
                </div>
              </div>
            </div>

            <div class="section-title">パートナーロゴ</div>
            <div class="field">
              <label>ロゴ1（home_partner_logo1）</label>
              <div class="page-image-row">
                <div class="img-preview"><img :src="getImageUrlByKey('home_partner_logo1') || ''" alt="preview"/></div>
                <div class="img-meta">
                  <div class="img-key">images.home_partner_logo1</div>
                  <div class="img-actions">
                    <input ref="img_home_partner_logo1" type="file" accept="image/*" style="display:none" @change="onCompanyImageSelected('home_partner_logo1', $event)" />
                    <button class="btn" @click="triggerCompanyImageUpload('home_partner_logo1')">アップロードファイル</button>
                  </div>
                </div>
              </div>
            </div>
            <div class="field">
              <label>ロゴ2（home_partner_logo2）</label>
              <div class="page-image-row">
                <div class="img-preview"><img :src="getImageUrlByKey('home_partner_logo2') || ''" alt="preview"/></div>
                <div class="img-meta">
                  <div class="img-key">images.home_partner_logo2</div>
                  <div class="img-actions">
                    <input ref="img_home_partner_logo2" type="file" accept="image/*" style="display:none" @change="onCompanyImageSelected('home_partner_logo2', $event)" />
                    <button class="btn" @click="triggerCompanyImageUpload('home_partner_logo2')">アップロードファイル</button>
                  </div>
                </div>
              </div>
            </div>
            <div class="field">
              <label>ロゴ3（home_partner_logo3）</label>
              <div class="page-image-row">
                <div class="img-preview"><img :src="getImageUrlByKey('home_partner_logo3') || ''" alt="preview"/></div>
                <div class="img-meta">
                  <div class="img-key">images.home_partner_logo3</div>
                  <div class="img-actions">
                    <input ref="img_home_partner_logo3" type="file" accept="image/*" style="display:none" @change="onCompanyImageSelected('home_partner_logo3', $event)" />
                    <button class="btn" @click="triggerCompanyImageUpload('home_partner_logo3')">アップロードファイル</button>
                  </div>
                </div>
              </div>
            </div>
            <div class="field">
              <label>ロゴ4（home_partner_logo4）</label>
              <div class="page-image-row">
                <div class="img-preview"><img :src="getImageUrlByKey('home_partner_logo4') || ''" alt="preview"/></div>
                <div class="img-meta">
                  <div class="img-key">images.home_partner_logo4</div>
                  <div class="img-actions">
                    <input ref="img_home_partner_logo4" type="file" accept="image/*" style="display:none" @change="onCompanyImageSelected('home_partner_logo4', $event)" />
                    <button class="btn" @click="triggerCompanyImageUpload('home_partner_logo4')">アップロードファイル</button>
                  </div>
                </div>
              </div>
            </div>

            <div class="section-title">バナー画像</div>
            <div class="field">
              <label>セミナー（banner_seminar）</label>
              <div class="page-image-row">
                <div class="img-preview"><img :src="getImageUrlByKey('banner_seminar') || ''" alt="preview"/></div>
                <div class="img-meta">
                  <div class="img-key">images.banner_seminar</div>
                  <div class="img-actions">
                    <input ref="img_banner_seminar" type="file" accept="image/*" style="display:none" @change="onCompanyImageSelected('banner_seminar', $event)" />
                    <button class="btn" @click="triggerCompanyImageUpload('banner_seminar')">アップロードファイル</button>
                  </div>
                </div>
              </div>
            </div>
            <div class="field">
              <label>刊行物（banner_publications）</label>
              <div class="page-image-row">
                <div class="img-preview"><img :src="getImageUrlByKey('banner_publications') || ''" alt="preview"/></div>
                <div class="img-meta">
                  <div class="img-key">images.banner_publications</div>
                  <div class="img-actions">
                    <input ref="img_banner_publications" type="file" accept="image/*" style="display:none" @change="onCompanyImageSelected('banner_publications', $event)" />
                    <button class="btn" @click="triggerCompanyImageUpload('banner_publications')">アップロードファイル</button>
                  </div>
                </div>
              </div>
            </div>
            <div class="field">
              <label>お知らせ（banner_info）</label>
              <div class="page-image-row">
                <div class="img-preview"><img :src="getImageUrlByKey('banner_info') || ''" alt="preview"/></div>
                <div class="img-meta">
                  <div class="img-key">images.banner_info</div>
                  <div class="img-actions">
                    <input ref="img_banner_info" type="file" accept="image/*" style="display:none" @change="onCompanyImageSelected('banner_info', $event)" />
                    <button class="btn" @click="triggerCompanyImageUpload('banner_info')">アップロードファイル</button>
                  </div>
                </div>
              </div>
            </div>
            <div class="field">
              <label>入会案内（banner_membership）</label>
              <div class="page-image-row">
                <div class="img-preview"><img :src="getImageUrlByKey('banner_membership') || ''" alt="preview"/></div>
                <div class="img-meta">
                  <div class="img-key">images.banner_membership</div>
                  <div class="img-actions">
                    <input ref="img_banner_membership" type="file" accept="image/*" style="display:none" @change="onCompanyImageSelected('banner_membership', $event)" />
                    <button class="btn" @click="triggerCompanyImageUpload('banner_membership')">アップロードファイル</button>
                  </div>
                </div>
              </div>
            </div>

            <div class="section-title">スライダー画像（3枚）</div>
            <div class="field">
              <label>スライド1（hero_slide_1）</label>
              <div class="page-image-row">
                <div class="img-preview"><img :src="getImageUrlByKey('hero_slide_1') || ''" alt="preview"/></div>
                <div class="img-meta">
                  <div class="img-key">images.hero_slide_1</div>
                  <div class="img-actions">
                    <input ref="img_hero_slide_1" type="file" accept="image/*" style="display:none" @change="onCompanyImageSelected('hero_slide_1', $event)" />
                    <button class="btn" @click="triggerCompanyImageUpload('hero_slide_1')">アップロードファイル</button>
                  </div>
                </div>
              </div>
            </div>
            <div class="field">
              <label>スライド2（hero_slide_2）</label>
              <div class="page-image-row">
                <div class="img-preview"><img :src="getImageUrlByKey('hero_slide_2') || ''" alt="preview"/></div>
                <div class="img-meta">
                  <div class="img-key">images.hero_slide_2</div>
                  <div class="img-actions">
                    <input ref="img_hero_slide_2" type="file" accept="image/*" style="display:none" @change="onCompanyImageSelected('hero_slide_2', $event)" />
                    <button class="btn" @click="triggerCompanyImageUpload('hero_slide_2')">アップロードファイル</button>
                  </div>
                </div>
              </div>
            </div>
            <div class="field">
              <label>スライド3（hero_slide_3）</label>
              <div class="page-image-row">
                <div class="img-preview"><img :src="getImageUrlByKey('hero_slide_3') || ''" alt="preview"/></div>
                <div class="img-meta">
                  <div class="img-key">images.hero_slide_3</div>
                  <div class="img-actions">
                    <input ref="img_hero_slide_3" type="file" accept="image/*" style="display:none" @change="onCompanyImageSelected('hero_slide_3', $event)" />
                    <button class="btn" @click="triggerCompanyImageUpload('hero_slide_3')">アップロードファイル</button>
                  </div>
                </div>
              </div>
            </div>
            <div class="help">未設定の場合はデフォルト画像が表示されます（/img/Image_fx*.jpg）。</div>
          </template>
          <template v-if="currentPage && (currentPage.slug==='seminar-application' || currentPage.slug.startsWith('seminar-application-')) && layoutMode">
            <div class="section-title">セミナー申込フォーム</div>
            <div class="field" v-for="key in seminarApplicationBasicKeys" :key="`seminar-app-basic-${key}`">
              <label>{{ displayLabel(key) }}</label>
              <input v-model="seminarApplicationTexts[key]" class="input" />
            </div>

            <div class="section-title">件名の選択肢</div>
            <div class="field" v-for="key in seminarApplicationOptionKeys" :key="`seminar-app-option-${key}`">
              <label>{{ displayLabel(key) }}</label>
              <input v-model="seminarApplicationTexts[key]" class="input" />
            </div>

            <div class="section-title">フォーム項目ラベル</div>
            <div class="field" v-for="key in seminarApplicationFieldLabelKeys" :key="`seminar-app-label-${key}`">
              <label>{{ displayLabel(key) }}</label>
              <input v-model="seminarApplicationTexts[key]" class="input" />
            </div>

            <div class="section-title">規約セクション（見出し）</div>
            <div class="field" v-for="key in seminarApplicationHeadingKeys" :key="`seminar-app-heading-${key}`">
              <label>{{ displayLabel(key) }}</label>
              <input v-model="seminarApplicationTexts[key]" class="input" />
            </div>

            <div class="section-title">規約本文・説明</div>
            <div class="field" v-for="key in seminarApplicationTextareaKeys" :key="`seminar-app-textarea-${key}`">
              <label>{{ displayLabel(key) }}</label>
              <textarea v-model="seminarApplicationTexts[key]" class="textarea" rows="4"></textarea>
            </div>

            <div class="section-title">規約同意・ボタン</div>
            <div class="field" v-for="key in seminarApplicationAgreementKeys" :key="`seminar-app-agree-${key}`">
              <label>{{ displayLabel(key) }}</label>
              <input v-model="seminarApplicationTexts[key]" class="input" />
            </div>

            <!-- 共通ヒーロー（メディアレジストリ：hero_seminar） -->
            <div class="section-title">ページ上部ヒーロー（共通）</div>
            <div class="field">
              <label>ヒーロー画像（hero_seminar）</label>
              <div class="page-image-row">
                <div class="img-preview"><img :src="getRegistryImageUrl('hero_seminar') || ''" alt="preview"/></div>
                <div class="img-meta">
                  <div class="img-key">media.hero_seminar</div>
                  <div class="img-actions">
                    <input ref="img_registry_hero_seminar" type="file" accept="image/*" style="display:none" @change="onReplaceRegistryImage('hero_seminar', $event)" />
                    <button class="btn" @click="triggerRegistryUpload('hero_seminar')">アップロードファイル</button>
                  </div>
                </div>
              </div>
              <div class="help">確認/完了ページ等でも共通で使用されます。</div>
            </div>
          </template>

          <template v-if="currentPage && currentPage.slug==='navigation' && layoutMode">
            <div class="section-title">ヘッダーナビ</div>
            <div class="field" v-for="key in navigationKeys" :key="`navigation-${key}`">
              <label>{{ displayLabel(key) }}</label>
              <input v-model="navigationTexts[key]" class="input" />
            </div>
          </template>

          <template v-if="currentPage && currentPage.slug==='footer' && layoutMode">
            <div class="section-title">フッターナビ</div>
            <div class="field" v-for="key in footerKeys" :key="`footer-${key}`">
              <label>{{ displayLabel(key) }}</label>
              <input v-model="footerTexts[key]" class="input" />
            </div>

            <div class="section-title">常設情報（住所・電話・営業時間・著作権）</div>
            <div class="field">
              <label>{{ displayLabel('footer_postal_code') }}</label>
              <input v-model="footerTexts.footer_postal_code" class="input" placeholder="例）〒839-0864" />
            </div>
            <div class="field">
              <label>{{ displayLabel('footer_address_line', true) }}</label>
              <textarea v-model="footerTexts.footer_address_line" class="textarea" rows="3" placeholder="住所（HTML可）"></textarea>
            </div>
            <div class="field">
              <label>{{ displayLabel('footer_phone_label') }}</label>
              <input v-model="footerTexts.footer_phone_label" class="input" placeholder="例）電話番号." />
              <label>{{ displayLabel('footer_phone_value') }}</label>
              <input v-model="footerTexts.footer_phone_value" class="input" placeholder="例）0942-46-5081" />
            </div>
            <div class="field">
              <label>{{ displayLabel('footer_hours_label') }}</label>
              <input v-model="footerTexts.footer_hours_label" class="input" placeholder="例）営業時間." />
              <label>{{ displayLabel('footer_hours_value') }}</label>
              <input v-model="footerTexts.footer_hours_value" class="input" placeholder="例）平日9:00-17:00" />
            </div>
            <div class="field">
              <label>{{ displayLabel('footer_copyright', true) }}</label>
              <textarea v-model="footerTexts.footer_copyright" class="textarea" rows="2" placeholder="Copyright 表示（HTML可）"></textarea>
            </div>
          </template>

          <div v-if="currentPage" class="section-title">子コンポーネント文言（基本）</div>
          <div v-if="currentPage" class="field">
            <label>ページタイトル（見出し）</label>
            <input v-if="currentPage.slug==='privacy-policy'" v-model="privacyTexts.page_title" class="input" />
            <input v-else-if="currentPage.slug==='terms'" v-model="termsTexts.page_title" class="input" />
            <input v-else-if="currentPage.slug==='transaction-law'" v-model="tlTexts.page_title" class="input" />
            <input v-else-if="currentPage.slug==='cri-consulting'" v-model="consultingTexts.page_title" class="input" />
            <input v-else-if="currentPage.slug==='company'" v-model="companyTexts.page_title" class="input" />
            <input v-else-if="currentPage.slug==='aboutus'" v-model="aboutTexts.page_title" class="input" />
            <input v-else-if="currentPage.slug==='about'" v-model="aboutTexts.page_title" class="input" />
            <input v-else-if="currentPage.slug==='membership' || currentPage.slug==='services'" v-model="membershipTexts.page_title" class="input" />
            <input v-else-if="currentPage.slug==='standard-membership'" v-model="standardTexts.page_title" class="input" />
            <input v-else-if="currentPage.slug==='premium-membership'" v-model="premiumTexts.page_title" class="input" />
            <input v-else-if="currentPage.slug==='contact'" v-model="contactTexts.page_title" class="input" />
            <input v-else-if="currentPage.slug && currentPage.slug.startsWith('seminars')" v-model="genericTexts.page_title" class="input" />
            <input v-else-if="currentPage.slug==='login'" v-model="loginTexts.page_title" class="input" />
            <input v-else-if="currentPage.slug==='membership-application'" v-model="membershipApplicationTexts.page_title" class="input" />
            <input v-else-if="currentPage.slug==='seminar-application'" v-model="seminarApplicationTexts.page_title" class="input" />
            <input v-else-if="currentPage.slug==='navigation'" v-model="navigationTexts.page_title" class="input" />
            <input v-else-if="currentPage.slug==='footer'" v-model="footerTexts.page_title" class="input" />
            <input v-else v-model="privacyTexts.page_title" class="input" />
          </div>
          <div v-if="currentPage" class="field">
            <label>サブタイトル</label>
            <input v-if="currentPage.slug==='privacy-policy'" v-model="privacyTexts.page_subtitle" class="input" />
            <input v-else-if="currentPage.slug==='terms'" v-model="termsTexts.page_subtitle" class="input" />
            <input v-else-if="currentPage.slug==='transaction-law'" v-model="tlTexts.page_subtitle" class="input" />
            <input v-else-if="currentPage.slug==='cri-consulting'" v-model="consultingTexts.page_subtitle" class="input" />
            <input v-else-if="currentPage.slug==='company'" v-model="companyTexts.page_subtitle" class="input" />
            <input v-else-if="currentPage.slug==='aboutus'" v-model="aboutTexts.page_subtitle" class="input" />
            <input v-else-if="currentPage.slug==='about'" v-model="aboutTexts.page_subtitle" class="input" />
            <input v-else-if="currentPage.slug==='membership' || currentPage.slug==='services'" v-model="membershipTexts.page_subtitle" class="input" />
            <input v-else-if="currentPage.slug==='standard-membership'" v-model="standardTexts.page_subtitle" class="input" />
            <input v-else-if="currentPage.slug==='premium-membership'" v-model="premiumTexts.page_subtitle" class="input" />
            <input v-else-if="currentPage.slug==='contact'" v-model="contactTexts.page_subtitle" class="input" />
            <input v-else-if="currentPage.slug && currentPage.slug.startsWith('seminars')" v-model="genericTexts.page_subtitle" class="input" />
            <input v-else-if="currentPage.slug==='login'" v-model="loginTexts.page_subtitle" class="input" />
            <input v-else-if="currentPage.slug==='membership-application'" v-model="membershipApplicationTexts.page_subtitle" class="input" />
            <input v-else-if="currentPage.slug==='seminar-application'" v-model="seminarApplicationTexts.page_subtitle" class="input" />
            <input v-else-if="currentPage.slug==='navigation'" v-model="navigationTexts.page_subtitle" class="input" />
            <input v-else-if="currentPage.slug==='footer'" v-model="footerTexts.page_subtitle" class="input" />
            <input v-else v-model="privacyTexts.page_subtitle" class="input" />
          </div>
          <div v-if="currentPage && currentPage.slug==='privacy-policy'" class="field">
            <label>導入文</label>
            <textarea v-model="privacyTexts.intro" class="textarea" rows="4"></textarea>
          </div>

          <!-- login: 導入文・ボタン文言 -->
          <template v-if="currentPage && currentPage.slug==='login' && layoutMode">
            <div class="section-title">ログインページ文言</div>
            <div class="field">
              <label>説明文（intro）</label>
              <textarea v-model="loginTexts.intro" class="textarea" rows="3"></textarea>
            </div>
            <div class="field">
              <label>ボタン文言</label>
              <input v-model="loginTexts.button_login" class="input" />
            </div>
          </template>

          <!-- privacy/terms/transaction-law: ヒーロー画像（レイアウト近似配置） -->
          <template v-if="currentPage && (currentPage.slug==='privacy-policy' || currentPage.slug==='terms' || currentPage.slug==='transaction-law')">
            <div class="section-title">ページ上部ヒーロー</div>
            <div class="field">
              <label>ヒーロー画像（hero）</label>
              <div class="page-image-row">
                <div class="img-preview"><img :src="getImageUrlByKey('hero') || ''" alt="preview"/></div>
                <div class="img-meta">
                  <div class="img-key">images.hero</div>
                  <div class="img-actions">
                    <input ref="img_hero_common" type="file" accept="image/*" style="display:none" @change="onCompanyImageSelected('hero', $event)" />
                    <button class="btn" @click="triggerCompanyImageUpload('hero')">アップロードファイル</button>
                  </div>
                </div>
              </div>
              <div class="help">推奨比率 16:9（md/lgプリセットで自動リサイズ配信）。</div>
            </div>
          </template>

          <!-- login: ヒーロー画像（レイアウト近似配置） -->
          <template v-if="currentPage && currentPage.slug==='login' && layoutMode">
            <div class="section-title">ページ上部ヒーロー</div>
            <div class="field">
              <label>ヒーロー画像（hero）</label>
              <div class="page-image-row">
                <div class="img-preview"><img :src="getImageUrlByKey('hero') || ''" alt="preview"/></div>
                <div class="img-meta">
                  <div class="img-key">images.hero</div>
                  <div class="img-actions">
                    <input ref="img_hero_login" type="file" accept="image/*" style="display:none" @change="onCompanyImageSelected('hero', $event)" />
                    <button class="btn" @click="triggerCompanyImageUpload('hero')">アップロードファイル</button>
                  </div>
                </div>
              </div>
              <div class="help">推奨比率 16:9（md/lgプリセットで自動リサイズ配信）。</div>
            </div>
          </template>

          <!-- economic/publications: ヒーロー画像（レイアウト近似配置） -->
          <template v-if="currentPage && (((currentPage.slug||'').toLowerCase().includes('economic')) || ((currentPage.slug||'').toLowerCase().includes('publications'))) && layoutMode">
            <div class="section-title">ページ上部ヒーロー</div>
            <div class="field">
              <label>ヒーロー画像（hero）</label>
              <div class="page-image-row">
                <div class="img-preview"><img :src="getImageUrlByKey('hero') || ''" alt="preview"/></div>
                <div class="img-meta">
                  <div class="img-key">images.hero</div>
                  <div class="img-actions">
                    <input ref="img_hero_economic" type="file" accept="image/*" style="display:none" @change="onCompanyImageSelected('hero', $event)" />
                    <button class="btn" @click="triggerCompanyImageUpload('hero')">アップロードファイル</button>
                  </div>
                </div>
              </div>
              <div class="help">推奨比率 16:9（md/lgプリセットで自動リサイズ配信）。</div>
            </div>
          </template>

          <!-- faq/glossary/sitemap: ヒーロー画像（レイアウト近似配置） -->
          <template v-if="currentPage && ((currentPage.slug==='faq' || currentPage.slug==='glossary' || currentPage.slug==='sitemap')) && layoutMode">
            <div class="section-title">ページ上部ヒーロー</div>
            <div class="field">
              <label>ヒーロー画像（hero）</label>
              <div class="page-image-row">
                <div class="img-preview"><img :src="getImageUrlByKey('hero') || ''" alt="preview"/></div>
                <div class="img-meta">
                  <div class="img-key">images.hero</div>
                  <div class="img-actions">
                    <input ref="img_hero_misc" type="file" accept="image/*" style="display:none" @change="onCompanyImageSelected('hero', $event)" />
                    <button class="btn" @click="triggerCompanyImageUpload('hero')">アップロードファイル</button>
                  </div>
                </div>
              </div>
              <div class="help">推奨比率 16:9（md/lgプリセットで自動リサイズ配信）。</div>
            </div>
          </template>
          

          <!-- company / consulting / about: 動的テキスト一覧（小コンポーネント） -->
          <div v-if="currentPage && (currentPage.slug==='company' || currentPage.slug==='cri-consulting' || currentPage.slug==='aboutus')" class="section-title">小コンポーネントの文言一覧（texts）</div>
          <template v-if="currentPage && ((currentPage.slug||'').toLowerCase().includes('company')) && !layoutMode">
            <div 
              class="field" 
              v-for="(val, key) in companyTexts" 
              :key="`company-${key}`"
              v-if="!(
                String(key).startsWith('staff_') ||
                key==='staff_title' ||
                key==='staff_subtitle' ||
                key==='access_title_3' ||
                key==='access_route_1' ||
                key==='access_route_2' ||
                key==='access_route_3'
              )"
            >
              <label>{{ displayLabel(key) }}</label>
              <input v-model="companyTexts[key]" class="input" />
            </div>
            <div class="help">スタッフの氏名・ふりがな・役職・注記は下の「所員紹介（Staff）」カードから編集してください。</div>
            <div class="field" v-for="(val, key) in companyHtmls" :key="`company-html-${key}`">
              <label>{{ displayLabel(key, true) }}</label>
              <textarea v-model="companyHtmls[key]" class="textarea" rows="3"></textarea>
            </div>
            <!-- Company history (沿革) -->
            <div class="section-title">沿革（history）</div>
            <div class="help">年/日付/本文(HTML) を編集できます</div>
            <div v-for="(h, idx) in companyHistory" :key="`hist-${idx}`" class="field" style="border:1px solid #eee; padding:10px; border-radius:8px;">
              <label>年（year）</label>
              <input v-model="h.year" class="input" placeholder="例: 2011" />
              <label>日付（date）</label>
              <input v-model="h.date" class="input" placeholder="例: 平成23年7月1日" />
              <label>本文（HTML）</label>
              <textarea v-model="h.body" class="textarea" rows="3" placeholder="本文（HTML）"></textarea>
              <div style="display:flex; gap:8px; justify-content:flex-end; margin-top:6px;">
                <button class="btn" @click="companyHistory.splice(idx,1)">削除</button>
                <button class="btn" @click="companyHistory.splice(Math.max(0, idx-1), 0, companyHistory.splice(idx,1)[0])" :disabled="idx===0">上へ</button>
                <button class="btn" @click="companyHistory.splice(Math.min(companyHistory.length, idx+2), 0, companyHistory.splice(idx,1)[0])" :disabled="idx===companyHistory.length-1">下へ</button>
              </div>
            </div>
            <div class="actions" style="justify-content:flex-start;">
              <button class="btn" @click="companyHistory.push({ year:'', date:'', body:'' })">+ 沿革を追加</button>
              <button class="btn" style="margin-left:8px;" @click="saveCompanyHistory">沿革を保存</button>
            </div>
          </template>

          <!-- Company: layout oriented editor -->
          <template v-if="currentPage && ((currentPage.slug||'').toLowerCase().includes('company')) && layoutMode">
            <!-- 経営理念（Philosophy） -->
            <div class="section-title">経営理念（Philosophy）</div>
            <div class="layout-grid">
              <div class="text-col">
                <div class="field">
                  <label>見出し</label>
                  <input v-model="companyTexts.philosophy_title" class="input" placeholder="経営理念" />
                </div>
                <div class="field">
                  <label>英字</label>
                  <input v-model="companyTexts.philosophy_subtitle" class="input" placeholder="philosophy" />
                </div>
                <div class="field">
                  <label>MISSION ラベル</label>
                  <input v-model="companyTexts.mission_label" class="input" placeholder="OUR MISSION" />
                </div>
                <div class="field">
                  <label>MISSION 見出し</label>
                  <input v-model="companyTexts.mission_title" class="input" placeholder="産官学金のネットワーク活用による地域貢献" />
                </div>
                <div class="field">
                  <label>MISSION 本文（HTML）</label>
                  <textarea v-model="companyHtmls.mission_body" class="textarea" rows="4"></textarea>
                </div>
              </div>
              <div class="image-col">
                <div class="field">
                  <label>セクション画像（company_profile_philosophy）</label>
                  <div class="page-image-row">
                    <div class="img-preview"><img :src="getImageUrlByKey('company_profile_philosophy') || ''" alt="preview"/></div>
                    <div class="img-meta">
                      <div class="img-key">images.company_profile_philosophy</div>
                      <div class="img-actions">
                        <input ref="img_company_profile_philosophy" type="file" accept="image/*" style="display:none" @change="onCompanyImageSelected('company_profile_philosophy', $event)" />
                        <button class="btn" @click="triggerCompanyImageUpload('company_profile_philosophy')">アップロードファイル</button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- ご挨拶（Message） -->
            <div class="section-title">ご挨拶（Message）</div>
            <div class="layout-grid">
              <div class="text-col">
                <div class="field">
                  <label>見出し</label>
                  <input v-model="companyTexts.message_title" class="input" placeholder="ご挨拶" />
                </div>
                <div class="field">
                  <label>英字</label>
                  <input v-model="companyTexts.message_subtitle" class="input" placeholder="message" />
                </div>
                <div class="field">
                  <label>本文（HTML）</label>
                  <textarea v-model="companyHtmls.message_body" class="textarea" rows="6"></textarea>
                </div>
                <div class="field">
                  <label>署名</label>
                  <input v-model="companyTexts.message_signature" class="input" placeholder="代表取締役社長 ・・・" />
                </div>
              </div>
              <div class="image-col">
                <div class="field">
                  <label>セクション画像（company_profile_message）</label>
                  <div class="page-image-row">
                    <div class="img-preview"><img :src="getImageUrlByKey('company_profile_message') || ''" alt="preview"/></div>
                    <div class="img-meta">
                      <div class="img-key">images.company_profile_message</div>
                      <div class="img-actions">
                        <input ref="img_company_profile_message" type="file" accept="image/*" style="display:none" @change="onCompanyImageSelected('company_profile_message', $event)" />
                        <button class="btn" @click="triggerCompanyImageUpload('company_profile_message')">アップロードファイル</button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- 会社概要（Company Profile） -->
            <div class="section-title">会社概要（Company Profile）</div>
            <div class="field">
              <label>見出し</label>
              <input v-model="companyTexts.profile_title" class="input" placeholder="会社概要" />
            </div>
            <div class="field">
              <label>英字</label>
              <input v-model="companyTexts.profile_subtitle" class="input" placeholder="company profile" />
            </div>
            <div class="field">
              <label>{{ displayLabel('profile_company_name_label') }}</label>
              <input v-model="companyTexts.profile_company_name_label" class="input" />
              <label>{{ displayLabel('profile_company_name_value') }}</label>
              <input v-model="companyTexts.profile_company_name_value" class="input" />
            </div>
            <div class="field">
              <label>{{ displayLabel('profile_established_label') }}</label>
              <input v-model="companyTexts.profile_established_label" class="input" />
              <label>{{ displayLabel('profile_established_value') }}</label>
              <input v-model="companyTexts.profile_established_value" class="input" />
            </div>
            <div class="field">
              <label>{{ displayLabel('profile_address_label') }}</label>
              <input v-model="companyTexts.profile_address_label" class="input" />
              <label>{{ displayLabel('profile_address_value', true) }}</label>
              <textarea v-model="companyHtmls.profile_address_value" class="textarea" rows="3"></textarea>
            </div>
            <div class="field">
              <label>{{ displayLabel('profile_representative_label') }}</label>
              <input v-model="companyTexts.profile_representative_label" class="input" />
              <label>{{ displayLabel('profile_representative_value') }}</label>
              <input v-model="companyTexts.profile_representative_value" class="input" />
            </div>
            <div class="field">
              <label>{{ displayLabel('profile_capital_label') }}</label>
              <input v-model="companyTexts.profile_capital_label" class="input" />
              <label>{{ displayLabel('profile_capital_value') }}</label>
              <input v-model="companyTexts.profile_capital_value" class="input" />
            </div>
            <div class="field">
              <label>{{ displayLabel('profile_shareholders_label') }}</label>
              <input v-model="companyTexts.profile_shareholders_label" class="input" />
              <label>{{ displayLabel('profile_shareholders_value') }}</label>
              <input v-model="companyTexts.profile_shareholders_value" class="input" />
            </div>
            <div class="field">
              <label>{{ displayLabel('profile_organization_label') }}</label>
              <input v-model="companyTexts.profile_organization_label" class="input" />
              <label>{{ displayLabel('profile_organization_value') }}</label>
              <input v-model="companyTexts.profile_organization_value" class="input" />
            </div>

            <!-- 沿革（History） -->
            <div class="section-title">沿革（History）</div>
            <div class="help">年/日付/本文(HTML) を編集できます</div>
            <div v-for="(h, idx) in companyHistory" :key="`hist-l-${idx}`" class="field" style="border:1px solid #eee; padding:10px; border-radius:8px;">
              <label>年（year）</label>
              <input v-model="h.year" class="input" placeholder="例: 2011" />
              <label>日付（date）</label>
              <input v-model="h.date" class="input" placeholder="例: 平成23年7月1日" />
              <label>本文（HTML）</label>
              <textarea v-model="h.body" class="textarea" rows="3" placeholder="本文（HTML）"></textarea>
              <div style="display:flex; gap:8px; justify-content:flex-end; margin-top:6px;">
                <button class="btn" @click="companyHistory.splice(idx,1)">削除</button>
                <button class="btn" @click="companyHistory.splice(Math.max(0, idx-1), 0, companyHistory.splice(idx,1)[0])" :disabled="idx===0">上へ</button>
                <button class="btn" @click="companyHistory.splice(Math.min(companyHistory.length, idx+2), 0, companyHistory.splice(idx,1)[0])" :disabled="idx===companyHistory.length-1">下へ</button>
              </div>
            </div>
            <div class="actions" style="justify-content:flex-start; gap:8px;">
              <button class="btn" @click="companyHistory.push({ year:'', date:'', body:'' })">+ 沿革を追加</button>
            </div>

            <!-- 所員紹介（Staff） -->
            <div class="section-title">所員紹介（Staff）</div>
            <div class="field">
              <label>見出し</label>
              <input v-model="companyTexts.staff_title" class="input" placeholder="所員紹介" />
            </div>
            <div class="field">
              <label>英字</label>
              <input v-model="companyTexts.staff_subtitle" class="input" placeholder="MEMBER" />
            </div>
            <div class="help">スタッフは上から順番に表示されます。必要に応じて追加・並べ替え・削除を行ってください。</div>
            <div
              v-for="(member, idx) in companyStaff"
              :key="member.__localId || `staff-${idx}`"
              class="field staff-card"
            >
              <div class="staff-card-header">
                <div class="staff-card-title">スタッフ {{ idx + 1 }}</div>
                <div class="staff-card-actions">
                  <button class="btn" @click="moveCompanyStaff(idx, -1)" :disabled="idx === 0">上へ</button>
                  <button class="btn" @click="moveCompanyStaff(idx, 1)" :disabled="idx === companyStaff.length - 1">下へ</button>
                  <button class="btn danger" @click="removeCompanyStaff(idx)">削除</button>
                </div>
              </div>
              <div class="field"><label>氏名</label><input v-model="member.name" class="input" placeholder="氏名" /></div>
              <div class="field"><label>ふりがな</label><input v-model="member.reading" class="input" placeholder="ふりがな" /></div>
              <div class="field"><label>役職</label><input v-model="member.position" class="input" placeholder="役職" /></div>
              <div class="field"><label>注記</label><input v-model="member.note" class="input" placeholder="（空でも可）" /></div>
              <div class="field"><label>ID / スラッグ</label><input v-model="member.id" class="input" placeholder="例: morita" /></div>
              <div class="field"><label>画像キー</label><input v-model="member.image_key" class="input" placeholder="company_profile_staff_***" @change="onCompanyStaffImageKeyChange(member)" /></div>
              <div class="field"><label>画像URL（任意）</label><input v-model="member.image_url" class="input" placeholder="直接URLを指定する場合" /></div>
              <div class="field">
                <label>画像プレビュー</label>
                <div class="page-image-row">
                  <div class="img-preview"><img :src="resolveCompanyStaffPreview(member)" alt="preview" /></div>
                  <div class="img-meta">
                    <div class="img-actions">
                      <button class="btn" @click="handleCompanyStaffImageUpload(member)" :disabled="!member.image_key">アップロードファイル</button>
                      <button class="btn" v-if="member.image_url" @click="clearCompanyStaffImageUrl(member)">URLをクリア</button>
                    </div>
                    <div class="help" v-if="!member.image_key">画像アップロード前にキーを入力してください</div>
                  </div>
                </div>
              </div>
            </div>
            <div class="actions" style="justify-content:flex-start; gap:8px; flex-wrap:wrap;">
              <button class="btn" @click="addCompanyStaff">+ 所員を追加</button>
              <button class="btn primary" @click="saveCompanyStaffUnified">所員を保存（統合）</button>
            </div>

            <!-- 決算報告（Financial Reports） -->
            <div class="section-title">決算報告（Financial Reports）</div>
            <div class="field">
              <label>見出し</label>
              <input v-model="companyTexts.financial_reports_title" class="input" placeholder="決算報告" />
            </div>
            <div class="field">
              <label>英字</label>
              <input v-model="companyTexts.financial_reports_subtitle" class="input" placeholder="financial reports" />
            </div>
            <!-- Financial reports editor (bridged to PageContent.content.financial_reports) -->
            <div class="help">年度ごとの決算項目とリンクを管理できます（公開でPageContentへ反映）。</div>
            <div class="actions" style="justify-content:flex-start;">
              <button class="btn" @click="addCompanyReport">+ 年度を追加</button>
              <button class="btn" @click="saveCompanyFinancialReports" style="margin-left:8px;">決算報告を保存</button>
            </div>
            <div v-for="(r, ri) in companyFinancialReports" :key="`fr-${ri}`" class="field" style="border:1px solid #eee; padding:10px; border-radius:8px; margin-bottom:8px;">
              <div style="display:grid; grid-template-columns: 1fr 1fr auto; gap:8px; align-items:center;">
                <div>
                  <label>年度</label>
                  <input v-model="r.fiscal_year" class="input" placeholder="例）2025年3月期" />
                </div>
                <div>
                  <label>日付/補足</label>
                  <input v-model="r.date_label" class="input" placeholder="例）決算短信（2025年5月12日）" />
                </div>
                <div style="display:flex; gap:6px; align-items:end;">
                  <button class="btn" @click="companyFinancialReports.splice(ri,1)">削除</button>
                </div>
              </div>
              <div style="margin-top:8px;">
                <label>項目（ラベルとURL）</label>
                <div v-for="(it, ii) in (r.items || (r.items = []))" :key="`r-${ri}-it-${ii}`" style="display:grid; grid-template-columns: 1.2fr 2fr auto auto; gap:8px; align-items:center; margin:6px 0;">
                  <input v-model="it.label" class="input" placeholder="例）決算要旨（PDF：...）" />
                  <input v-model="it.url" class="input" placeholder="/storage/media/xxx.pdf または https://..." />
                  <button class="btn" @click="uploadCompanyReportItemPdf(ri, ii)">PDFアップ</button>
                  <button class="btn" @click="removeCompanyReportItem(ri, ii)">削除</button>
                </div>
                <div class="actions" style="justify-content:flex-start;">
                  <button class="btn" @click="addCompanyReportItem(ri)">+ 項目を追加</button>
                </div>
              </div>
            </div>

          </template>

          <!-- Company: Access (アクセス) editable fields -->
          <template v-if="currentPage && ((currentPage.slug||'').toLowerCase().includes('company')) && layoutMode">
            <div class="section-title">アクセス（Access）</div>
            <!-- ブロック1 -->
            <div class="field"><label>見出し1</label><input v-model="companyTexts.access_title_1" class="input" placeholder="所在地" /></div>
            <div class="field"><label>英字/サブ1</label><input v-model="companyTexts.access_subtitle_1" class="input" placeholder="address" /></div>
            <div class="field"><label>本文1（HTML）</label><textarea v-model="companyHtmls.access_body_1" class="textarea" rows="4"></textarea></div>
            <div class="field"><label>電話番号</label><input v-model="companyTexts.access_phone" class="input" placeholder="TEL: 000-000-0000" /></div>
            <div class="field"><label>営業時間</label><input v-model="companyTexts.access_hours" class="input" placeholder="9:00-17:00（平日）" /></div>

            <!-- ブロック2（最寄り） -->
            <div class="section-title">最寄り（Nearest）</div>
            <div class="field"><label>見出し2</label><input v-model="companyTexts.access_title_2" class="input" placeholder="最寄駅・バス" /></div>
            <div class="field"><label>駅1</label><input v-model="companyTexts.access_station_1" class="input" /></div>
            <div class="field"><label>駅2</label><input v-model="companyTexts.access_station_2" class="input" /></div>
            <div class="field"><label>バス</label><input v-model="companyTexts.access_bus_1" class="input" /></div>

            <!-- ブロック3（車でのアクセス） 非表示 -->
            <template v-if="false">
              <div class="section-title">ルート（By Car）</div>
              <div class="field"><label>見出し3</label><input v-model="companyTexts.access_title_3" class="input" placeholder="お車でお越しの方" /></div>
              <div class="field"><label>ルート1</label><input v-model="companyTexts.access_route_1" class="input" /></div>
              <div class="field"><label>ルート2</label><input v-model="companyTexts.access_route_2" class="input" /></div>
              <div class="field"><label>ルート3</label><input v-model="companyTexts.access_route_3" class="input" /></div>
            </template>

            
          </template>

          <!-- AboutUs: layout oriented editor -->
          <template v-if="currentPage && ((currentPage.slug||'').toLowerCase().includes('aboutus')) && layoutMode">
            <!-- 研究所について（About） -->
            <div class="section-title">研究所について（About）</div>
            <div class="layout-grid">
              <div class="text-col">
                <div class="field">
                  <label>見出し</label>
                  <input v-model="aboutTexts.about_title" class="input" placeholder="研究所について" />
                </div>
                <div class="field">
                  <label>英字</label>
                  <input v-model="aboutTexts.about_subtitle" class="input" placeholder="About us" />
                </div>
                <div class="field">
                  <label>本文（HTML）</label>
                  <textarea v-model="aboutHtmls.about_body" class="textarea" rows="6"></textarea>
                </div>
              </div>
              <div class="image-col">
                <div class="field">
                  <label>セクション画像（images.content）</label>
                  <div class="page-image-row">
                    <div class="img-preview"><img :src="getImageUrlByKey('content') || ''" alt="preview"/></div>
                    <div class="img-meta">
                      <div class="img-key">images.content</div>
                      <div class="img-actions">
                        <input ref="img_about_content" type="file" accept="image/*" style="display:none" @change="onCompanyImageSelected('content', $event)" />
                        <button class="btn" @click="triggerCompanyImageUpload('content')">アップロードファイル</button>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="field">
                  <label>セクション画像（about_image）</label>
                  <div class="page-image-row">
                    <div class="img-preview"><img :src="getImageUrlByKey('about_image') || ''" alt="preview"/></div>
                    <div class="img-meta">
                      <div class="img-key">images.about_image</div>
                      <div class="img-actions">
                        <input ref="img_about_image" type="file" accept="image/*" style="display:none" @change="onCompanyImageSelected('about_image', $event)" />
                        <button class="btn" @click="triggerCompanyImageUpload('about_image')">アップロードファイル</button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- サービス（Services） -->
            <div class="section-title">サービス（Services）</div>
            <div class="field">
              <label>見出し</label>
              <input v-model="aboutTexts.service_title" class="input" placeholder="サービス概要" />
            </div>
            <div class="field">
              <label>英字</label>
              <input v-model="aboutTexts.service_subtitle" class="input" placeholder="services" />
            </div>

            <div class="field">
              <label>サービス1（見出し）</label>
              <input v-model="aboutTexts.service1_title" class="input" />
              <label>サービス1（説明）</label>
              <input v-model="aboutTexts.service1_desc" class="input" />
              <label>サービス1（リストHTML）</label>
              <textarea v-model="aboutHtmls.service1_list" class="textarea" rows="4"></textarea>
            </div>
            <div class="field">
              <label>サービス1（service1_image）</label>
              <div class="page-image-row">
                <div class="img-preview"><img :src="getImageUrlByKey('service1_image') || ''" alt="preview"/></div>
                <div class="img-meta">
                  <div class="img-key">images.service1_image</div>
                  <div class="img-actions">
                    <input ref="img_service1_image" type="file" accept="image/*" style="display:none" @change="onCompanyImageSelected('service1_image', $event)" />
                    <button class="btn" @click="triggerCompanyImageUpload('service1_image')">アップロードファイル</button>
                  </div>
                </div>
              </div>
            </div>
            <div class="field">
              <label>サービス2（見出し）</label>
              <input v-model="aboutTexts.service2_title" class="input" />
              <label>サービス2（説明）</label>
              <input v-model="aboutTexts.service2_desc" class="input" />
              <label>サービス2（リストHTML）</label>
              <textarea v-model="aboutHtmls.service2_list" class="textarea" rows="4"></textarea>
            </div>
            <div class="field">
              <label>サービス2（service2_image）</label>
              <div class="page-image-row">
                <div class="img-preview"><img :src="getImageUrlByKey('service2_image') || ''" alt="preview"/></div>
                <div class="img-meta">
                  <div class="img-key">images.service2_image</div>
                  <div class="img-actions">
                    <input ref="img_service2_image" type="file" accept="image/*" style="display:none" @change="onCompanyImageSelected('service2_image', $event)" />
                    <button class="btn" @click="triggerCompanyImageUpload('service2_image')">アップロードファイル</button>
                  </div>
                </div>
              </div>
            </div>
            <div class="field">
              <label>サービス3（見出し）</label>
              <input v-model="aboutTexts.service3_title" class="input" />
              <label>サービス3（説明）</label>
              <input v-model="aboutTexts.service3_desc" class="input" />
              <label>サービス3（リストHTML）</label>
              <textarea v-model="aboutHtmls.service3_list" class="textarea" rows="4"></textarea>
            </div>
            <div class="field">
              <label>サービス3（service3_image）</label>
              <div class="page-image-row">
                <div class="img-preview"><img :src="getImageUrlByKey('service3_image') || ''" alt="preview"/></div>
                <div class="img-meta">
                  <div class="img-key">images.service3_image</div>
                  <div class="img-actions">
                    <input ref="img_service3_image" type="file" accept="image/*" style="display:none" @change="onCompanyImageSelected('service3_image', $event)" />
                    <button class="btn" @click="triggerCompanyImageUpload('service3_image')">アップロードファイル</button>
                  </div>
                </div>
              </div>
            </div>

            
          </template>

          <!-- About (旧 about): layout oriented editor -->
          <template v-if="currentPage && currentPage.slug==='about' && layoutMode">
            <!-- 経営理念（Philosophy） / ミッション -->
            <div class="section-title">経営理念（Philosophy）</div>
            <div class="layout-grid">
              <div class="text-col">
                <div class="field">
                  <label>見出し</label>
                  <input v-model="aboutTexts.philosophy_title" class="input" placeholder="経営理念" />
                </div>
                <div class="field">
                  <label>英字</label>
                  <input v-model="aboutTexts.philosophy_subtitle" class="input" placeholder="OUR MISSION" />
                </div>
                <div class="field">
                  <label>ミッション見出し</label>
                  <input v-model="aboutTexts.mission_title" class="input" placeholder="産学官金のネットワークを活かした地域創生" />
                </div>
                <div class="field">
                  <label>ミッション本文</label>
                  <textarea v-model="aboutTexts.mission_text" class="textarea" rows="5"></textarea>
                </div>
              </div>
              <div class="image-col">
                <div class="field">
                  <label>セクション画像（images.content）</label>
                  <div class="page-image-row">
                    <div class="img-preview"><img :src="getImageUrlByKey('content') || ''" alt="preview"/></div>
                    <div class="img-meta">
                      <div class="img-key">images.content</div>
                      <div class="img-actions">
                        <input ref="img_about_content_old" type="file" accept="image/*" style="display:none" @change="onCompanyImageSelected('content', $event)" />
                        <button class="btn" @click="triggerCompanyImageUpload('content')">アップロードファイル</button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- ご挨拶（Message） -->
            <div class="section-title">ご挨拶（Message）</div>
            <div class="layout-grid">
              <div class="text-col">
                <div class="field"><label>見出し</label><input v-model="aboutTexts.message_title" class="input" /></div>
                <div class="field"><label>英字</label><input v-model="aboutTexts.message_subtitle" class="input" /></div>
                <div class="field"><label>本文（HTML）</label><textarea v-model="aboutHtmls.message_body" class="textarea" rows="6"></textarea></div>
                <div class="field"><label>署名</label><input v-model="aboutTexts.message_signature" class="input" /></div>
              </div>
              <div class="image-col">
                <div class="field">
                  <label>セクション画像（images.message）</label>
                  <div class="page-image-row">
                    <div class="img-preview"><img :src="getImageUrlByKey('message') || ''" alt="preview"/></div>
                    <div class="img-meta">
                      <div class="img-key">images.message</div>
                      <div class="img-actions">
                        <input ref="img_about_message_old" type="file" accept="image/*" style="display:none" @change="onCompanyImageSelected('message', $event)" />
                        <button class="btn" @click="triggerCompanyImageUpload('message')">アップロードファイル</button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- 会社概要（Company） -->
            <div class="section-title">会社概要（Company）</div>
            <div class="field"><label>見出し</label><input v-model="aboutTexts.company_title" class="input" /></div>
            <div class="field"><label>英字</label><input v-model="aboutTexts.company_subtitle" class="input" /></div>
            <div class="field">
              <label>会社名（ラベル/値）</label>
              <input v-model="aboutTexts.company_label_name" class="input" />
              <input v-model="aboutTexts.company_value_name" class="input" />
            </div>
            <div class="field">
              <label>設立（ラベル/値）</label>
              <input v-model="aboutTexts.company_label_established" class="input" />
              <input v-model="aboutTexts.company_value_established" class="input" />
            </div>
            <div class="field">
              <label>所在地（ラベル/値HTML）</label>
              <input v-model="aboutTexts.company_label_address" class="input" />
              <textarea v-model="aboutHtmls.company_value_address" class="textarea" rows="3"></textarea>
            </div>
            <div class="field">
              <label>電話（ラベル/値）</label>
              <input v-model="aboutTexts.company_label_tel" class="input" />
              <input v-model="aboutTexts.company_value_tel" class="input" />
            </div>
            <div class="field">
              <label>営業時間（ラベル/値）</label>
              <input v-model="aboutTexts.company_label_business_hours" class="input" />
              <input v-model="aboutTexts.company_value_business_hours" class="input" />
            </div>

            <!-- 所員紹介（Staff） -->
            <div class="section-title">所属紹介（Staff）</div>
            <div class="help">このセクションの文言・氏名・役職は「会社概要（/company）」で編集します。About 側は company-profile の文言を参照して表示のみです。</div>

            
          </template>

          <!-- CRI Consulting: layout oriented editor -->
          <template v-if="currentPage && ((currentPage.slug||'').toLowerCase().includes('consult')) && layoutMode">
            <!-- クリとは？ -->
            <div class="section-title">CRIとは？（What is CRI Consulting）</div>
            <div class="field"><label>見出し</label><input v-model="consultingTexts.what_title" class="input" /></div>
            <div class="field"><label>英字</label><input v-model="consultingTexts.what_subtitle" class="input" /></div>
            <div class="field"><label>説明サブ見出し</label><input v-model="consultingTexts.what_content_subtitle" class="input" /></div>
            <div class="field"><label>説明見出し</label><input v-model="consultingTexts.what_content_heading" class="input" /></div>
            <div class="field"><label>説明（本文HTML）</label><textarea v-model="consultingHtmls.what_content_body" class="textarea" rows="5"></textarea></div>

            <!-- 主な業務 -->
            <div class="section-title">主な業務（Main Duties）</div>
            <div class="field"><label>見出し</label><input v-model="consultingTexts.duties_title" class="input" /></div>
            <div class="field"><label>英字</label><input v-model="consultingTexts.duties_subtitle" class="input" /></div>
            <div class="field"><label>導入（HTML）</label><textarea v-model="consultingHtmls.duties_intro" class="textarea" rows="4"></textarea></div>
            <div class="field"><label>ラベル</label><input v-model="consultingTexts.duties_label" class="input" /></div>
            <div class="field"><label>説明見出し</label><input v-model="consultingTexts.duties_heading" class="input" /></div>
            <div class="field"><label>リスト（HTML）</label><textarea v-model="consultingHtmls.duties_list" class="textarea" rows="5"></textarea></div>

            <!-- サポート内容と費用 -->
            <div class="section-title">サポート内容と費用（Support & Costs）</div>
            <div class="field"><label>英字</label><input v-model="consultingTexts.support_subtitle_en" class="input" /></div>
            <div class="field"><label>導入（HTML）</label><textarea v-model="consultingHtmls.support_intro" class="textarea" rows="4"></textarea></div>
            <div class="field"><label>無料（見出し）</label><input v-model="consultingTexts.support_free_title" class="input" /></div>
            <div class="field"><label>有料（見出し）</label><input v-model="consultingTexts.support_paid_title" class="input" /></div>

            <!-- 無料サポート（画像） -->
            <div class="section-title">無料サポート（画像）</div>
            <div class="field">
              <label>相談（support_free_consultation）</label>
              <div class="page-image-row">
                <div class="img-preview"><img :src="getImageUrlByKey('support_free_consultation') || ''" alt="preview"/></div>
                <div class="img-meta">
                  <div class="img-actions">
                    <input ref="img_support_free_consultation" type="file" accept="image/*" style="display:none" @change="onCompanyImageSelected('support_free_consultation', $event)" />
                    <button class="btn" @click="triggerCompanyImageUpload('support_free_consultation')">アップロードファイル</button>
                  </div>
                </div>
              </div>
            </div>
            <div class="field">
              <label>問題把握（support_free_problem）</label>
              <div class="page-image-row">
                <div class="img-preview"><img :src="getImageUrlByKey('support_free_problem') || ''" alt="preview"/></div>
                <div class="img-meta">
                  <div class="img-actions">
                    <input ref="img_support_free_problem" type="file" accept="image/*" style="display:none" @change="onCompanyImageSelected('support_free_problem', $event)" />
                    <button class="btn" @click="triggerCompanyImageUpload('support_free_problem')">アップロードファイル</button>
                  </div>
                </div>
              </div>
            </div>

            <!-- 有料サポート（画像） -->
            <div class="section-title">有料サポート（画像）</div>
            <div class="field">
              <label>戦略策定（support_paid_strategy）</label>
              <div class="page-image-row">
                <div class="img-preview"><img :src="getImageUrlByKey('support_paid_strategy') || ''" alt="preview"/></div>
                <div class="img-meta">
                  <div class="img-actions">
                    <input ref="img_support_paid_strategy" type="file" accept="image/*" style="display:none" @change="onCompanyImageSelected('support_paid_strategy', $event)" />
                    <button class="btn" @click="triggerCompanyImageUpload('support_paid_strategy')">アップロードファイル</button>
                  </div>
                </div>
              </div>
            </div>
            <div class="field">
              <label>改善活動（support_paid_improvement）</label>
              <div class="page-image-row">
                <div class="img-preview"><img :src="getImageUrlByKey('support_paid_improvement') || ''" alt="preview"/></div>
                <div class="img-meta">
                  <div class="img-actions">
                    <input ref="img_support_paid_improvement" type="file" accept="image/*" style="display:none" @change="onCompanyImageSelected('support_paid_improvement', $event)" />
                    <button class="btn" @click="triggerCompanyImageUpload('support_paid_improvement')">アップロードファイル</button>
                  </div>
                </div>
              </div>
            </div>
            <div class="field">
              <label>分析（support_paid_analysis）</label>
              <div class="page-image-row">
                <div class="img-preview"><img :src="getImageUrlByKey('support_paid_analysis') || ''" alt="preview"/></div>
                <div class="img-meta">
                  <div class="img-actions">
                    <input ref="img_support_paid_analysis" type="file" accept="image/*" style="display:none" @change="onCompanyImageSelected('support_paid_analysis', $event)" />
                    <button class="btn" @click="triggerCompanyImageUpload('support_paid_analysis')">アップロードファイル</button>
                  </div>
                </div>
              </div>
            </div>
            <div class="field">
              <label>新規事業活動（support_paid_newbiz）</label>
              <div class="page-image-row">
                <div class="img-preview"><img :src="getImageUrlByKey('support_paid_newbiz') || ''" alt="preview"/></div>
                <div class="img-meta">
                  <div class="img-actions">
                    <input ref="img_support_paid_newbiz" type="file" accept="image/*" style="display:none" @change="onCompanyImageSelected('support_paid_newbiz', $event)" />
                    <button class="btn" @click="triggerCompanyImageUpload('support_paid_newbiz')">アップロードファイル</button>
                  </div>
                </div>
              </div>
            </div>

            <!-- 実績（任意） -->
            <div class="section-title">実績紹介（Achievements）</div>
            <div class="field"><label>見出し</label><input v-model="consultingTexts.achievements_title" class="input" /></div>
            <div class="field"><label>英字</label><input v-model="consultingTexts.achievements_subtitle" class="input" /></div>

            <!-- ヒーロー画像 -->
            <div class="section-title">ページ上部ヒーロー</div>
            <div class="field">
              <label>ヒーロー画像（hero）</label>
              <div class="page-image-row">
                <div class="img-preview"><img :src="getImageUrlByKey('hero') || ''" alt="preview"/></div>
                <div class="img-meta">
                  <div class="img-key">images.hero</div>
                  <div class="img-actions">
                    <input ref="img_hero" type="file" accept="image/*" style="display:none" @change="onCompanyImageSelected('hero', $event)" />
                    <button class="btn" @click="triggerCompanyImageUpload('hero')">アップロードファイル</button>
                  </div>
                </div>
              </div>
              <div class="help">PC のヒーロー背景に使用されます。差し替え後は即座にデスクトップにも反映されます。</div>
              <div class="help">上部の「KV画像」でもヒーローを設定できます（推奨）。</div>
            </div>

            <!-- 画像（レイアウト近似配置） -->
            <div class="layout-grid">
              <div class="image-col" style="flex:1;">
                <div class="field">
                  <label>「CRIとは？」画像（what_image）</label>
                  <div class="page-image-row">
                    <div class="img-preview"><img :src="getImageUrlByKey('what_image') || ''" alt="preview"/></div>
                    <div class="img-meta">
                      <div class="img-key">images.what_image</div>
                      <div class="img-actions">
                        <input ref="img_what_image" type="file" accept="image/*" style="display:none" @change="onCompanyImageSelected('what_image', $event)" />
                        <button class="btn" @click="triggerCompanyImageUpload('what_image')">アップロードファイル</button>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="field">
                  <label>主な業務 画像（duties_image）</label>
                  <div class="page-image-row">
                    <div class="img-preview"><img :src="getImageUrlByKey('duties_image') || ''" alt="preview"/></div>
                    <div class="img-meta">
                      <div class="img-key">images.duties_image</div>
                      <div class="img-actions">
                        <input ref="img_duties_image" type="file" accept="image/*" style="display:none" @change="onCompanyImageSelected('duties_image', $event)" />
                        <button class="btn" @click="triggerCompanyImageUpload('duties_image')">アップロードファイル</button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="image-col" style="flex:1;">
                <div class="field">
                  <label>実績1（achievements_item1_image）</label>
                  <div class="page-image-row">
                    <div class="img-preview"><img :src="getImageUrlByKey('achievements_item1_image') || ''" alt="preview"/></div>
                    <div class="img-meta">
                      <div class="img-actions">
                        <input ref="img_achievements_item1_image" type="file" accept="image/*" style="display:none" @change="onCompanyImageSelected('achievements_item1_image', $event)" />
                        <button class="btn" @click="triggerCompanyImageUpload('achievements_item1_image')">アップロードファイル</button>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="field">
                  <label>実績2（achievements_item2_image）</label>
                  <div class="page-image-row">
                    <div class="img-preview"><img :src="getImageUrlByKey('achievements_item2_image') || ''" alt="preview"/></div>
                    <div class="img-meta">
                      <div class="img-actions">
                        <input ref="img_achievements_item2_image" type="file" accept="image/*" style="display:none" @change="onCompanyImageSelected('achievements_item2_image', $event)" />
                        <button class="btn" @click="triggerCompanyImageUpload('achievements_item2_image')">アップロードファイル</button>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="field">
                  <label>実績3（achievements_item3_image）</label>
                  <div class="page-image-row">
                    <div class="img-preview"><img :src="getImageUrlByKey('achievements_item3_image') || ''" alt="preview"/></div>
                    <div class="img-meta">
                      <div class="img-actions">
                        <input ref="img_achievements_item3_image" type="file" accept="image/*" style="display:none" @change="onCompanyImageSelected('achievements_item3_image', $event)" />
                        <button class="btn" @click="triggerCompanyImageUpload('achievements_item3_image')">アップロードファイル</button>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="field">
                  <label>実績4（achievements_item4_image）</label>
                  <div class="page-image-row">
                    <div class="img-preview"><img :src="getImageUrlByKey('achievements_item4_image') || ''" alt="preview"/></div>
                    <div class="img-meta">
                      <div class="img-actions">
                        <input ref="img_achievements_item4_image" type="file" accept="image/*" style="display:none" @change="onCompanyImageSelected('achievements_item4_image', $event)" />
                        <button class="btn" @click="triggerCompanyImageUpload('achievements_item4_image')">アップロードファイル</button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            
          </template>
          <template v-if="currentPage && currentPage.slug==='cri-consulting'">
            <div class="section-title">小ラベル（FREE/PAID）</div>
            <div class="field"><label>FREE ラベル①</label><input v-model="consultingTexts.free_consultation_label" class="input" placeholder="consultation" /></div>
            <div class="field"><label>FREE ラベル②</label><input v-model="consultingTexts.free_problem_label" class="input" placeholder="problem" /></div>
            <div class="field"><label>PAID ラベル：対応策の立案</label><input v-model="consultingTexts.paid_strategy_label" class="input" placeholder="solutions" /></div>
            <div class="field"><label>PAID ラベル：改善に向けた活動</label><input v-model="consultingTexts.paid_improvement_label" class="input" placeholder="improvement" /></div>
            <div class="field"><label>PAID ラベル：分析</label><input v-model="consultingTexts.paid_analysis_label" class="input" placeholder="analysis" /></div>
            <div class="field"><label>PAID ラベル：新たな事業活動</label><input v-model="consultingTexts.paid_newbiz_label" class="input" placeholder="new business" /></div>

            <div class="section-title">テキスト一覧（その他）</div>
            <div class="field" v-for="(val, key) in consultingTexts" :key="`consult-${key}`">
              <label>{{ displayLabel(key) }}</label>
              <input v-model="consultingTexts[key]" class="input" />
            </div>
            <div class="field" v-for="(val, key) in consultingHtmls" :key="`consult-html-${key}`">
              <label>{{ displayLabel(key, true) }}</label>
              <textarea v-model="consultingHtmls[key]" class="textarea" rows="3"></textarea>
            </div>
          </template>
          <template v-if="currentPage && currentPage.slug==='aboutus'">
            <div class="field" v-for="(val, key) in aboutTexts" :key="`about-${key}`">
              <label>{{ displayLabel(key) }}</label>
              <input v-model="aboutTexts[key]" class="input" />
            </div>
            <div class="field" v-for="(val, key) in aboutHtmls" :key="`about-html-${key}`">
              <label>{{ displayLabel(key, true) }}</label>
              <textarea v-model="aboutHtmls[key]" class="textarea" rows="3"></textarea>
            </div>
          </template>

          <!-- glossary: 用語リスト編集 -->
          <div v-if="currentPage && currentPage.slug==='glossary'" class="section-title">用語リスト（items）</div>
          <template v-if="currentPage && currentPage.slug==='glossary'">
            <div class="help">用語（term / category / definition(HTML)）を編集・追加できます</div>
            <div v-for="(it, idx) in glossaryItems" :key="`gls-${idx}`" class="field" style="border:1px solid #eee; padding:10px; border-radius:8px;">
              <label>用語（term）</label>
              <input v-model="it.term" class="input" placeholder="例: CPI" />
              <label>カテゴリ（category）</label>
              <input v-model="it.category" class="input" placeholder="例: economic" />
              <label>定義（HTML）</label>
              <textarea v-model="it.definition" class="textarea" rows="4" placeholder="定義をHTMLで入力"></textarea>
              <div style="display:flex; gap:8px; justify-content:flex-end; margin-top:6px;">
                <button class="btn" @click="glossaryItems.splice(idx,1)">削除</button>
                <button class="btn" @click="glossaryItems.splice(Math.max(0, idx-1), 0, glossaryItems.splice(idx,1)[0])" :disabled="idx===0">上へ</button>
                <button class="btn" @click="glossaryItems.splice(Math.min(glossaryItems.length, idx+2), 0, glossaryItems.splice(idx,1)[0])" :disabled="idx===glossaryItems.length-1">下へ</button>
              </div>
            </div>
            <div class="actions" style="justify-content:flex-start;">
              <button class="btn" @click="glossaryItems.push({ term:'', category:'', definition:'' })">+ 用語を追加</button>
            </div>
          </template>

          <!-- FAQ: Q/A リスト編集 -->
          <div v-if="currentPage && currentPage.slug==='faq'" class="section-title">FAQ 項目（faqs）</div>
          <template v-if="currentPage && currentPage.slug==='faq'">
            <div class="help">カテゴリ/質問/回答(HTML)/タグ（カンマ区切り）を編集・追加できます</div>
            <div v-for="(fq, idx) in faqItems" :key="`faq-${idx}`" class="field" style="border:1px solid #eee; padding:10px; border-radius:8px;">
              <label>カテゴリ</label>
              <input v-model="fq.category" class="input" placeholder="all | service | membership | research" />
              <label>質問</label>
              <input v-model="fq.question" class="input" placeholder="質問文" />
              <label>回答（HTML）</label>
              <textarea v-model="fq.answer" class="textarea" rows="4" placeholder="回答HTML"></textarea>
              <label>タグ（カンマ区切り）</label>
              <input :value="(Array.isArray(fq.tags)? fq.tags.join(', ') : '')" @input="(e)=>{ const v=e.target.value||''; fq.tags = v.split(',').map(s=>s.trim()).filter(Boolean) }" class="input" placeholder="例: 会費,手続き" />
              <div style="display:flex; gap:8px; justify-content:flex-end; margin-top:6px;">
                <button class="btn" @click="faqItems.splice(idx,1)">削除</button>
                <button class="btn" @click="faqItems.splice(Math.max(0, idx-1), 0, faqItems.splice(idx,1)[0])" :disabled="idx===0">上へ</button>
                <button class="btn" @click="faqItems.splice(Math.min(faqItems.length, idx+2), 0, faqItems.splice(idx,1)[0])" :disabled="idx===faqItems.length-1">下へ</button>
              </div>
            </div>
            <div class="actions" style="justify-content:flex-start;">
              <button class="btn" @click="faqItems.push({ category:'all', question:'', answer:'', tags:[], _id: faqItems.length })">+ FAQを追加</button>
            </div>
          </template>

          <!-- フォールバック: 汎用のtexts/htmlsエディタ（除外ページ以外） -->
          <template v-if="currentPage && showGenericEditor">
            <div class="section-title">子コンポーネント文言（基本）</div>
            <div class="field" v-for="(val, key) in genericTexts" :key="`gtext-${key}`">
              <label>{{ displayLabel(key) }}</label>
              <input v-model="genericTexts[key]" class="input" />
            </div>
            <div class="section-title">セクション別文言（HTML）</div>
            <div class="field" v-for="(val, key) in genericHtmls" :key="`ghtml-${key}`">
              <label>{{ displayLabel(key, true) }}</label>
              <textarea v-model="genericHtmls[key]" class="textarea" rows="3"></textarea>
            </div>
          </template>

          <!-- privacy-policy: section-wise fields -->
          <div v-if="currentPage.slug==='privacy-policy'" class="section-title">セクション別文言</div>
          <!-- 1. 収集 -->
          <div v-if="currentPage.slug==='privacy-policy'" class="field">
            <label>1. 個人情報の収集（見出し）</label>
            <input v-model="privacyTexts.collection_title" class="input" />
          </div>
          <div v-if="currentPage.slug==='privacy-policy'" class="field">
            <label>1. 個人情報の収集（本文）</label>
            <textarea v-model="privacyTexts.collection_body" class="textarea" rows="4"></textarea>
          </div>
          <!-- 2. 利用目的 -->
          <div v-if="currentPage.slug==='privacy-policy'" class="field">
            <label>2. 個人情報の利用目的（見出し）</label>
            <input v-model="privacyTexts.purpose_title" class="input" />
          </div>
          <div v-if="currentPage.slug==='privacy-policy'" class="field">
            <label>2. 個人情報の利用目的（導入文）</label>
            <textarea v-model="privacyTexts.purpose_intro" class="textarea" rows="3"></textarea>
          </div>
          <div v-if="currentPage.slug==='privacy-policy'" class="field">
            <label>2. 個人情報の利用目的（リストHTML）</label>
            <textarea v-model="privacyTexts.purpose_list" class="textarea" rows="5" placeholder="<ul>は不要。<br>区切りで入力"></textarea>
          </div>
          <!-- 3. 第三者提供 -->
          <div v-if="currentPage.slug==='privacy-policy'" class="field">
            <label>3. 個人情報の第三者提供（見出し）</label>
            <input v-model="privacyTexts.disclosure_title" class="input" />
          </div>
          <div v-if="currentPage.slug==='privacy-policy'" class="field">
            <label>3. 個人情報の第三者提供（リストHTML）</label>
            <textarea v-model="privacyTexts.disclosure_list" class="textarea" rows="5"></textarea>
          </div>
          <!-- 4. 開示・訂正・削除 -->
          <div v-if="currentPage.slug==='privacy-policy'" class="field">
            <label>4. 個人情報の開示・訂正・削除（見出し）</label>
            <input v-model="privacyTexts.correction_title" class="input" />
          </div>
          <div v-if="currentPage.slug==='privacy-policy'" class="field">
            <label>4. 個人情報の開示・訂正・削除（本文HTML）</label>
            <textarea v-model="privacyTexts.correction_body" class="textarea" rows="5"></textarea>
          </div>
          <!-- 免責 -->
          <div v-if="currentPage.slug==='privacy-policy'" class="field">
            <label>免責事項（見出し）</label>
            <input v-model="privacyTexts.disclaimer_title" class="input" />
          </div>
          <div v-if="currentPage.slug==='privacy-policy'" class="field">
            <label>免責事項（本文1）</label>
            <textarea v-model="privacyTexts.disclaimer_body1" class="textarea" rows="3"></textarea>
          </div>
          <div v-if="currentPage.slug==='privacy-policy'" class="field">
            <label>免責事項（本文2）</label>
            <textarea v-model="privacyTexts.disclaimer_body2" class="textarea" rows="3"></textarea>
          </div>
          <div v-if="currentPage.slug==='privacy-policy'" class="field">
            <label>免責事項（本文3）</label>
            <textarea v-model="privacyTexts.disclaimer_body3" class="textarea" rows="3"></textarea>
          </div>
          <!-- 変更告知 -->
          <div v-if="currentPage.slug==='privacy-policy'" class="field">
            <label>プライバシーポリシーの変更（見出し）</label>
            <input v-model="privacyTexts.changes_title" class="input" />
          </div>
          <div v-if="currentPage.slug==='privacy-policy'" class="field">
            <label>プライバシーポリシーの変更（本文）</label>
            <textarea v-model="privacyTexts.changes_body" class="textarea" rows="3"></textarea>
          </div>

          <!-- 利用規約 セクション別文言 -->
          <div v-if="currentPage.slug==='terms'" class="section-title">セクション別文言（利用規約）</div>
          <template v-if="currentPage.slug==='terms'">
            <div class="field"><label>導入文（HTML）</label><textarea v-model="termsHtmls.intro" class="textarea" rows="4"></textarea></div>
            <div class="field"><label>著作権（見出し）</label><input v-model="termsTexts.copyright_title" class="input" /></div>
            <div class="field"><label>著作権（本文HTML）</label><textarea v-model="termsHtmls.copyright_body" class="textarea" rows="4"></textarea></div>
            <div class="field"><label>リンク（見出し）</label><input v-model="termsTexts.link_title" class="input" /></div>
            <div class="field"><label>リンク（本文HTML）</label><textarea v-model="termsHtmls.link_body" class="textarea" rows="4"></textarea></div>
            <div class="field"><label>免責事項（見出し）</label><input v-model="termsTexts.disclaimer_title" class="input" /></div>
            <div class="field"><label>免責事項（本文HTML）</label><textarea v-model="termsHtmls.disclaimer_body" class="textarea" rows="4"></textarea></div>
            <div class="field"><label>セキュリティ（見出し）</label><input v-model="termsTexts.security_title" class="input" /></div>
            <div class="field"><label>セキュリティ（本文HTML）</label><textarea v-model="termsHtmls.security_body" class="textarea" rows="4"></textarea></div>
            <div class="field"><label>クッキー（見出し）</label><input v-model="termsTexts.cookie_title" class="input" /></div>
            <div class="field"><label>クッキー（本文HTML）</label><textarea v-model="termsHtmls.cookie_body" class="textarea" rows="4"></textarea></div>
            <div class="field"><label>ご利用環境（見出し）</label><input v-model="termsTexts.environment_title" class="input" /></div>
            <div class="field"><label>ご利用環境（本文HTML）</label><textarea v-model="termsHtmls.environment_body" class="textarea" rows="4"></textarea></div>
            <div class="field"><label>禁止される行為（見出し）</label><input v-model="termsTexts.prohibited_title" class="input" /></div>
            <div class="field"><label>禁止される行為（本文HTML）</label><textarea v-model="termsHtmls.prohibited_body" class="textarea" rows="4"></textarea></div>
            <div class="field"><label>第8条（見出し）</label><input v-model="termsTexts.article8_title" class="input" /></div>
            <div class="field"><label>第8条（本文HTML）</label><textarea v-model="termsHtmls.article8_body" class="textarea" rows="4"></textarea></div>
          </template>

          <!-- 特定商取引法 セクション別文言 -->
          <div v-if="currentPage.slug==='transaction-law'" class="section-title">セクション別文言（特定商取引法）</div>
          <template v-if="currentPage.slug==='transaction-law'">
            <div class="field"><label>販売業者（ラベル）</label><input v-model="tlTexts.seller_label" class="input" /></div>
            <div class="field"><label>販売業者（値）</label><input v-model="tlTexts.seller_value" class="input" /></div>
            <div class="field"><label>代表者名（ラベル）</label><input v-model="tlTexts.rep_label" class="input" /></div>
            <div class="field"><label>代表者名（値）</label><input v-model="tlTexts.rep_value" class="input" /></div>
            <div class="field"><label>住所（ラベル）</label><input v-model="tlTexts.addr_label" class="input" /></div>
            <div class="field"><label>住所（HTML）</label><textarea v-model="tlHtmls.addr_value" class="textarea" rows="3"></textarea></div>
            <div class="field"><label>電話番号（ラベル）</label><input v-model="tlTexts.tel_label" class="input" /></div>
            <div class="field"><label>電話番号（値）</label><input v-model="tlTexts.tel_value" class="input" /></div>
            <div class="field"><label>FAX番号（ラベル）</label><input v-model="tlTexts.fax_label" class="input" /></div>
            <div class="field"><label>FAX番号（値）</label><input v-model="tlTexts.fax_value" class="input" /></div>
            <div class="field"><label>メール（ラベル）</label><input v-model="tlTexts.mail_label" class="input" /></div>
            <div class="field"><label>メール（値）</label><input v-model="tlTexts.mail_value" class="input" /></div>
            <div class="field"><label>お問い合わせCTA</label><input v-model="tlTexts.contact_cta" class="input" /></div>
            <div class="field"><label>料金（ラベル）</label><input v-model="tlTexts.fee_label" class="input" /></div>
            <div class="field"><label>料金（説明HTML）</label><textarea v-model="tlHtmls.fee_desc" class="textarea" rows="3"></textarea></div>
            <div class="field"><label>料金セクション見出し</label><input v-model="tlTexts.fee_section_title" class="input" /></div>
            <div class="field"><label>スタンダード会員（見出し）</label><input v-model="tlTexts.fee_standard_label" class="input" /></div>
            <div class="field"><label>スタンダード会員（金額）</label><input v-model="tlTexts.fee_standard_amount" class="input" /></div>
            <div class="field"><label>プレミアムネット会員（見出し）</label><input v-model="tlTexts.fee_premium_label" class="input" /></div>
            <div class="field"><label>プレミアムネット会員（金額）</label><input v-model="tlTexts.fee_premium_amount" class="input" /></div>
            <div class="field"><label>支払い時期および方法（ラベル）</label><input v-model="tlTexts.payment_label" class="input" /></div>
            <div class="field"><label>支払い時期および方法（本文HTML）</label><textarea v-model="tlHtmls.payment_body" class="textarea" rows="3"></textarea></div>
            <div class="field"><label>その他料金（ラベル）</label><input v-model="tlTexts.otherfees_label" class="input" /></div>
            <div class="field"><label>その他料金（値）</label><input v-model="tlTexts.otherfees_value" class="input" /></div>
            <div class="field"><label>提供時間（ラベル）</label><input v-model="tlTexts.service_time_label" class="input" /></div>
            <div class="field"><label>提供時間（値）</label><input v-model="tlTexts.service_time_value" class="input" /></div>
            <div class="field"><label>退会（ラベル）</label><input v-model="tlTexts.cancel_label" class="input" /></div>
            <div class="field"><label>退会（値）</label><input v-model="tlTexts.cancel_value" class="input" /></div>
            <div class="field"><label>返金（ラベル）</label><input v-model="tlTexts.refund_label" class="input" /></div>
            <div class="field"><label>返金（本文HTML）</label><textarea v-model="tlHtmls.refund_body" class="textarea" rows="3"></textarea></div>
            <div class="field"><label>会員規約注記</label><input v-model="tlTexts.terms_note" class="input" /></div>

            <div class="section-title">規約テキスト（第1条〜第3条）</div>
            <div class="field"><label>第1条（見出し）</label><input v-model="tlTexts.article1_title" class="input" /></div>
            <div class="field"><label>第1条（本文・テキスト）</label><textarea v-model="tlTexts.article1_body" class="textarea" rows="4"></textarea></div>
            <div class="field"><label>第2条（見出し）</label><input v-model="tlTexts.article2_title" class="input" /></div>
            <div class="field"><label>第2条（本文・HTML）</label><textarea v-model="tlHtmls.article2_body" class="textarea" rows="6"></textarea></div>
            <div class="field"><label>第3条（見出し）</label><input v-model="tlTexts.article3_title" class="input" /></div>
            <div class="field"><label>第3条（導入テキスト）</label><textarea v-model="tlTexts.article3_body" class="textarea" rows="3"></textarea></div>

            <div class="section-title">会員サービス（第3条・リスト）</div>
            <div class="field"><label>スタンダード会員（見出し）</label><input v-model="tlTexts.standard_title" class="input" /></div>
            <div class="field"><label>スタンダード①</label><input v-model="tlTexts.standard_item1" class="input" /></div>
            <div class="field"><label>スタンダード②</label><input v-model="tlTexts.standard_item2" class="input" /></div>
            <div class="field"><label>スタンダード③</label><input v-model="tlTexts.standard_item3" class="input" /></div>
            <div class="field"><label>スタンダード④</label><input v-model="tlTexts.standard_item4" class="input" /></div>
            <div class="field"><label>プレミアムネット会員（見出し）</label><input v-model="tlTexts.premium_title" class="input" /></div>
            <div class="field"><label>プレミアム（導入）</label><input v-model="tlTexts.premium_intro" class="input" /></div>
            <div class="field"><label>プレミアム⑤</label><input v-model="tlTexts.premium_item5" class="input" /></div>
            <div class="field"><label>プレミアム⑥</label><input v-model="tlTexts.premium_item6" class="input" /></div>
            <div class="field"><label>プレミアム⑦</label><input v-model="tlTexts.premium_item7" class="input" /></div>

            <div class="field"><label>規約PDFリンク文言</label><input v-model="tlTexts.terms_download_label" class="input" /></div>
          </template>

          <div class="actions-row">
            <button class="btn primary" :disabled="!currentPage" @click="publish">公開する</button>
            <button class="btn" :disabled="!currentPage" @click="unpublish">公開を停止する</button>
            <button class="btn" :disabled="!currentPage" @click="issuePreview" v-if="false">プレビューリンク</button>
            <a v-if="previewUrl" :href="previewUrl" target="_blank" rel="noopener" class="btn">開く</a>
          </div>

          <div v-if="currentPage" class="actions-row" style="margin-top:8px;">
            <button class="btn primary" @click="savePrivacyTexts">文言を保存（PageContent）</button>
            <button class="btn" @click="importExistingPrivacy" v-if="false">既存文言を取り込む</button>
            <button class="btn" @click="syncRichToPageContentHtml" v-if="false">本文をPageContentに同期</button>
          </div>
        <div v-if="currentPage" class="field" style="margin-top:8px;">
          <label>PageContentのページキー（必要に応じて変更）</label>
          <input v-model="pageContentKey" class="input" placeholder="privacy / privacy-poricy など" />
          <div class="help">取り込み/保存はこのキーで行います</div>
        </div>

        <!-- Page images management (optional) -->
        <div v-if="currentPage" class="section-title" style="display:flex; align-items:center; gap:8px;">
          ページ内画像（content.images）
          <label style="margin-left:auto; font-weight:normal; font-size:12px; color:#555; display:flex; gap:6px; align-items:center;">
            <input type="checkbox" v-model="showImageList" /> 一覧を表示
          </label>
        </div>
        <!-- 未設定画像チェック -->
        <div v-if="currentPage && missingImageKeys && missingImageKeys.length" class="field" style="background:#fff7e6; border:1px solid #ffd8a8; padding:10px; border-radius:8px;">
          <div style="font-weight:600; color:#9c5700; margin-bottom:6px;">未設定の画像キー</div>
          <div style="display:flex; flex-wrap:wrap; gap:6px;">
            <div v-for="k in missingImageKeys" :key="`miss-${k}`" style="display:flex; gap:6px; align-items:center; background:#fff; border:1px solid #ffe8cc; border-radius:6px; padding:6px 8px;">
              <code style="background:transparent; padding:0; color:#9c5700;">{{ k }}</code>
              <button class="btn" style="padding:4px 8px;" @click="uploadForKey(k)">アップロード</button>
            </div>
          </div>
          <!-- 一覧が非表示でもアップロードできるように、ここにも隠しinputを常設する -->
          <input ref="newImageInputGlobal" type="file" accept="image/*" style="display:none" @change="onAddNewPageImage" />
        </div>
        <div v-if="currentPage && showImageList" class="field">
          <div v-if="pageImages.length === 0" class="help">登録済みの画像がありません</div>
          <div v-for="(img, idx) in pageImages" :key="`pimg-${idx}`" class="page-image-row">
            <div class="img-preview" v-if="img.url"><img :src="img.url" alt="preview"/></div>
            <div class="img-meta">
              <div class="img-key">{{ img.key }}</div>
              <div class="img-file">{{ img.filename || '' }}</div>
              <div class="img-actions">
                <input :ref="`replace_${idx}`" type="file" accept="image/*" style="display:none" @change="onReplacePageImage(idx, $event)" />
                <button class="btn" @click="triggerReplace(idx)">差し替え</button>
              </div>
            </div>
          </div>
          <div class="actions-row" style="margin-top:8px; gap:8px; align-items:center;">
            <input v-model="newImageKey" class="input" placeholder="新規キー（例: hero, content.main など）" style="max-width:260px" />
            <input ref="newImageInput" type="file" accept="image/*" style="display:none" @change="onAddNewPageImage" />
            <button class="btn" @click="triggerAddNew">新規追加</button>
            <span class="help">キー未入力の場合は追加できません</span>
          </div>
        </div>
        <!-- どの表示状態でも確実に使えるフォールバックのhidden input -->
        <input ref="newImageInputAny" type="file" accept="image/*" style="display:none" @change="onAddNewPageImage" />

        <!-- Media key overrides (content.media_keys) -->
        <div v-if="currentPage" class="section-title">メディアキー割当（media_keys）</div>
        <div v-if="currentPage" class="field">
          <div v-for="(val, key) in mediaKeys" :key="`mkey-${key}`" style="display:flex; gap:8px; align-items:center; margin-bottom:6px;">
            <div style="width:220px;">
              <label>スロット</label>
              <input class="input" :value="key" disabled />
            </div>
            <div style="flex:1;">
              <label>mediaキー</label>
              <input class="input" v-model="mediaKeys[key]" placeholder="例: hero_terms" />
            </div>
            <button class="btn" @click="delete mediaKeys[key]">削除</button>
          </div>
          <div style="display:flex; gap:8px; align-items:center;">
            <input class="input" v-model="newMediaSlot" placeholder="スロット（例: hero）" style="max-width:220px;" />
            <input class="input" v-model="newMediaKey" placeholder="mediaキー（例: hero_terms）" />
            <button class="btn" @click="()=>{ if(newMediaSlot&&newMediaKey){ mediaKeys[newMediaSlot]=newMediaKey; newMediaSlot=''; newMediaKey=''; } }">追加</button>
        </div>
        <div class="help">指定したスロットはページの画像解決時に優先されます</div>
      </div>

      <!-- Home: About cards images -->
      <div v-if="currentPage && currentPage.slug==='home'" class="section-title">トップ: 研究所についてカード画像</div>
      <div v-if="currentPage && currentPage.slug==='home'" class="field">
        <label>カード1（キー: about_card1_image）</label>
        <div class="page-image-row">
          <div class="img-preview"><img :src="getImageUrlByKey('about_card1_image') || ''" alt="preview"/></div>
          <div class="img-meta">
            <div class="img-actions">
              <button class="btn" @click="uploadForKey('about_card1_image')">アップロードファイル</button>
            </div>
          </div>
        </div>
      </div>
      <div v-if="currentPage && currentPage.slug==='home'" class="field">
        <label>カード2（キー: about_card2_image）</label>
        <div class="page-image-row">
          <div class="img-preview"><img :src="getImageUrlByKey('about_card2_image') || ''" alt="preview"/></div>
          <div class="img-meta">
            <div class="img-actions">
              <button class="btn" @click="uploadForKey('about_card2_image')">アップロードファイル</button>
            </div>
          </div>
        </div>
      </div>
      <div v-if="currentPage && currentPage.slug==='home'" class="field">
        <label>カード3（キー: about_card3_image）</label>
        <div class="page-image-row">
          <div class="img-preview"><img :src="getImageUrlByKey('about_card3_image') || ''" alt="preview"/></div>
          <div class="img-meta">
            <div class="img-actions">
              <button class="btn" @click="uploadForKey('about_card3_image')">アップロードファイル</button>
            </div>
          </div>
        </div>
      </div>

      <!-- Home: Partner logos -->
      <div v-if="currentPage && currentPage.slug==='home'" class="section-title">トップ: パートナーロゴ</div>
      <div v-if="currentPage && currentPage.slug==='home'" class="field">
        <label>ロゴ1（キー: home_partner_logo1）</label>
        <div class="page-image-row">
          <div class="img-preview"><img :src="getImageUrlByKey('home_partner_logo1') || ''" alt="preview"/></div>
          <div class="img-meta">
            <div class="img-actions">
              <button class="btn" @click="uploadForKey('home_partner_logo1')">アップロードファイル</button>
            </div>
          </div>
        </div>
      </div>
      <div v-if="currentPage && currentPage.slug==='home'" class="field">
        <label>ロゴ2（キー: home_partner_logo2）</label>
        <div class="page-image-row">
          <div class="img-preview"><img :src="getImageUrlByKey('home_partner_logo2') || ''" alt="preview"/></div>
          <div class="img-meta">
            <div class="img-actions">
              <button class="btn" @click="uploadForKey('home_partner_logo2')">アップロードファイル</button>
            </div>
          </div>
        </div>
      </div>
      <div v-if="currentPage && currentPage.slug==='home'" class="field">
        <label>ロゴ3（キー: home_partner_logo3）</label>
        <div class="page-image-row">
          <div class="img-preview"><img :src="getImageUrlByKey('home_partner_logo3') || ''" alt="preview"/></div>
          <div class="img-meta">
            <div class="img-actions">
              <button class="btn" @click="uploadForKey('home_partner_logo3')">アップロードファイル</button>
            </div>
          </div>
        </div>
      </div>
      <div v-if="currentPage && currentPage.slug==='home'" class="field">
        <label>ロゴ4（キー: home_partner_logo4）</label>
        <div class="page-image-row">
          <div class="img-preview"><img :src="getImageUrlByKey('home_partner_logo4') || ''" alt="preview"/></div>
          <div class="img-meta">
            <div class="img-actions">
              <button class="btn" @click="uploadForKey('home_partner_logo4')">アップロードファイル</button>
            </div>
          </div>
        </div>
      </div>

      <!-- Home: Hero Slider Images (3 slides) -->
      <div v-if="currentPage && currentPage.slug==='home'" class="section-title">トップ: スライダー画像（3枚）</div>
        <div v-if="currentPage && currentPage.slug==='home'" class="field">
          <label>スライド1（キー: hero_slide_1）</label>
          <div class="page-image-row">
            <div class="img-preview"><img :src="getImageUrlByKey('hero_slide_1') || ''" alt="preview"/></div>
            <div class="img-meta">
              <div class="img-actions">
                <input ref="img_hero_slide_1" type="file" accept="image/*" style="display:none" @change="onHomeSlideSelected('hero_slide_1', $event)" />
                <button class="btn" @click="triggerHomeSlideUpload('hero_slide_1')">アップロードファイル</button>
              </div>
            </div>
          </div>
        </div>
        <div v-if="currentPage && currentPage.slug==='home'" class="field">
          <label>スライド2（キー: hero_slide_2）</label>
          <div class="page-image-row">
            <div class="img-preview"><img :src="getImageUrlByKey('hero_slide_2') || ''" alt="preview"/></div>
            <div class="img-meta">
              <div class="img-actions">
                <input ref="img_hero_slide_2" type="file" accept="image/*" style="display:none" @change="onHomeSlideSelected('hero_slide_2', $event)" />
                <button class="btn" @click="triggerHomeSlideUpload('hero_slide_2')">アップロードファイル</button>
              </div>
            </div>
          </div>
        </div>
        <div v-if="currentPage && currentPage.slug==='home'" class="field">
          <label>スライド3（キー: hero_slide_3）</label>
          <div class="page-image-row">
            <div class="img-preview"><img :src="getImageUrlByKey('hero_slide_3') || ''" alt="preview"/></div>
            <div class="img-meta">
              <div class="img-actions">
                <input ref="img_hero_slide_3" type="file" accept="image/*" style="display:none" @change="onHomeSlideSelected('hero_slide_3', $event)" />
                <button class="btn" @click="triggerHomeSlideUpload('hero_slide_3')">アップロードファイル</button>
              </div>
            </div>
          </div>
          <div class="help">未設定の場合はデフォルト画像が表示されます（/img/Image_fx*.jpg）。</div>
        </div>
      </div>
      <div v-else class="empty">ページを選択してください</div>
    </div>
      <div class="pane right" style="display:none"></div>
    </div>

    <!-- Create modal -->
    <div v-if="showCreate" class="modal" @click="showCreate=false">
      <div class="modal-inner" @click.stop>
        <h3>新規ページ</h3>
        <div class="field"><label>Slug</label><input v-model="createForm.slug" class="input" placeholder="about, terms など"/></div>
        <div class="field"><label>タイトル</label><input v-model="createForm.title" class="input"/></div>
        <div class="actions"><button class="btn" @click="create">作成</button></div>
      </div>
    </div>
  </AdminLayout>
  
</template>

<script>
import AdminLayout from './AdminLayout.vue'
import apiClient from '@/services/apiClient'
import { getApiUrl } from '@/config/api.js'

const CONTACT_BASIC_KEYS = ['form_title', 'form_label_subject', 'form_placeholder_select']
const CONTACT_OPTION_KEYS = ['form_option_inquiry', 'form_option_membership', 'form_option_seminar', 'form_option_other']
const CONTACT_FIELD_LABEL_KEYS = [
  'form_label_name',
  'form_label_kana',
  'form_label_company',
  'form_label_position',
  'form_label_phone',
  'form_label_email',
  'form_label_content'
]
const CONTACT_TEXTAREA_KEYS = ['form_privacy_note']
const CONTACT_AGREEMENT_KEYS = ['form_privacy_agree', 'form_button_confirm']
const CONTACT_INFO_KEYS = ['contact_title', 'contact_label', 'contact_subtitle', 'contact_cta']

const TRANSPARENT_PIXEL = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7'
const COMPANY_STAFF_LEGACY = [
  {
    id: 'morita',
    name: '森田 祥子',
    reading: 'もりた さちこ',
    position: '企画部　部長代理',
    note: '（アジア福岡パートナーズへ出向）',
    nameKey: 'staff_morita_name',
    readingKey: 'staff_morita_reading',
    positionKey: 'staff_morita_position',
    noteKey: 'staff_morita_note',
    imageKey: 'company_profile_staff_morita',
    fallbackImage: 'https://api.builder.io/api/v1/image/assets/TEMP/013d1cd8a9cd502c97404091dee8168d1aa93903?width=452',
  },
  {
    id: 'mizokami',
    name: '溝上 浩文',
    reading: 'みぞかみ ひろふみ',
    position: '取締役企画部長　兼調査部長',
    note: '',
    nameKey: 'staff_mizokami_name',
    readingKey: 'staff_mizokami_reading',
    positionKey: 'staff_mizokami_position',
    noteKey: 'staff_mizokami_note',
    imageKey: 'company_profile_staff_mizokami',
    fallbackImage: 'https://api.builder.io/api/v1/image/assets/TEMP/3eb35c11c5738cb9283fd65048f0db5c42dd1080?width=451',
  },
  {
    id: 'kuga',
    name: '空閑 重信',
    reading: 'くが しげのぶ',
    position: '代表取締役社長',
    note: '',
    nameKey: 'staff_kuga_name',
    readingKey: 'staff_kuga_reading',
    positionKey: 'staff_kuga_position',
    noteKey: 'staff_kuga_note',
    imageKey: 'company_profile_staff_kuga',
    fallbackImage: 'https://api.builder.io/api/v1/image/assets/TEMP/ce433d9c00a0ce68895c315df3a3c49aa626deff?width=451',
  },
  {
    id: 'takada',
    name: '髙田 友里恵',
    reading: 'たかだ ゆりえ',
    position: '調査部　主任',
    note: '',
    nameKey: 'staff_takada_name',
    readingKey: 'staff_takada_reading',
    positionKey: 'staff_takada_position',
    noteKey: 'staff_takada_note',
    imageKey: 'company_profile_staff_takada',
    fallbackImage: 'https://api.builder.io/api/v1/image/assets/TEMP/b21372a6aca15dfc189c6953aeb23f36f5d5e20b?width=451',
  },
  {
    id: 'nakamura',
    name: '中村 公栄',
    reading: 'なかむら きえみ',
    position: '',
    note: '',
    nameKey: 'staff_nakamura_name',
    readingKey: 'staff_nakamura_reading',
    positionKey: 'staff_nakamura_position',
    noteKey: 'staff_nakamura_note',
    imageKey: 'company_profile_staff_nakamura',
    fallbackImage: 'https://api.builder.io/api/v1/image/assets/TEMP/497e67c9baa8add863ab6c5cc32439cf23eea4c3?width=451',
  },
]

const APPLICATION_BASIC_KEYS = ['page_title', 'page_subtitle', 'form_title', 'form_label_subject', 'form_placeholder_select']
const APPLICATION_OPTION_KEYS = ['form_option_standard', 'form_option_premium', 'form_option_inquiry', 'form_option_other']
const APPLICATION_FIELD_LABEL_KEYS = CONTACT_FIELD_LABEL_KEYS
const APPLICATION_HEADING_KEYS = ['terms_article1_title', 'terms_article2_title', 'terms_article3_title', 'standard_title', 'premium_title', 'terms_download_label']
const APPLICATION_TEXTAREA_KEYS = [
  'form_privacy_note',
  'terms_article1_body',
  'terms_article2_body',
  'terms_article3_intro',
  'standard_item1',
  'standard_item2',
  'standard_item3',
  'standard_item4',
  'premium_intro',
  'premium_item5',
  'premium_item6',
  'premium_item7'
]
const APPLICATION_AGREEMENT_KEYS = ['form_privacy_agree', 'form_button_confirm']

const NAVIGATION_KEYS = [
  'nav_about',
  'nav_seminars',
  'nav_publications',
  'nav_information',
  'nav_membership',
  'nav_premium_benefits',
  'nav_news',
  'nav_company',
  'nav_faq',
  'nav_indicators',
  'nav_statistics',
  'nav_membership_standard',
  'nav_membership_premium'
]

const FOOTER_KEYS = [
  'link_home',
  'link_company',
  'link_about',
  'link_news',
  'link_faq',
  'link_contact',
  'link_seminar',
  'link_publication',
  'link_glossary',
  'link_membership',
  'link_membership_standard',
  'link_membership_premium',
  'link_cri_consulting',
  'link_member_login',
  'link_legal',
  'link_privacy',
  'link_terms',
  'link_sitemap'
]

export default {
  name: 'BlockCmsEditor',
  components: { AdminLayout },
  data(){
    return {
      pages: [],
      search: '',
      currentPage: null,
      sections: [],
      hero: { title: '' },
      richText: { html: '' },
      warnings: [],
      showCreate: false,
      createForm: { slug: '', title: '' },
      previewUrl: '',
      lastContentImgUrl: '',
      // エディタ（本文）の表示切替。既定は非表示
      showContentEditor: false,
      // ページ構成ビュー（実ページに近い配置で編集）: 既定ON（ローカル保存がなければ）
      layoutMode: true,
      privacyTexts: {
        page_title: '', page_subtitle: '', intro: '',
        collection_title: '', collection_body: '',
        purpose_title: '', purpose_intro: '', purpose_list: '',
        disclosure_title: '', disclosure_list: '',
        correction_title: '', correction_body: '',
        disclaimer_title: '', disclaimer_body1: '', disclaimer_body2: '', disclaimer_body3: '',
        changes_title: '', changes_body: '',
      },
      // 利用規約（セクション別）: APIから動的にマージするため空で初期化
      termsTexts: {},
      termsHtmls: {},
      // 特定商取引法（セクション別）: APIから動的にマージするため空で初期化
      tlTexts: {},
      tlHtmls: {},
      // 会社概要 / コンサル / 研究所について（小コンポーネント文言）
      companyTexts: {},
      companyHtmls: {},
      companyStaff: [],
      companyFinancialReports: [],
      consultingTexts: {},
      consultingHtmls: {},
      aboutTexts: {},
      aboutHtmls: {},
      membershipTexts: {},
      standardTexts: {},
      premiumTexts: {},
      contactTexts: {},
      contactHtmls: {},
      membershipApplicationTexts: {},
      seminarApplicationTexts: {},
      navigationTexts: {},
      footerTexts: {},
      contactBasicKeys: CONTACT_BASIC_KEYS,
      contactOptionKeys: CONTACT_OPTION_KEYS,
      contactFieldLabelKeys: CONTACT_FIELD_LABEL_KEYS,
      contactTextareaKeys: CONTACT_TEXTAREA_KEYS,
      contactAgreementKeys: CONTACT_AGREEMENT_KEYS,
      contactInfoKeys: CONTACT_INFO_KEYS,
      membershipApplicationBasicKeys: APPLICATION_BASIC_KEYS,
      membershipApplicationOptionKeys: APPLICATION_OPTION_KEYS,
      membershipApplicationFieldLabelKeys: APPLICATION_FIELD_LABEL_KEYS,
      membershipApplicationHeadingKeys: APPLICATION_HEADING_KEYS,
      membershipApplicationTextareaKeys: APPLICATION_TEXTAREA_KEYS,
      membershipApplicationAgreementKeys: APPLICATION_AGREEMENT_KEYS,
      seminarApplicationBasicKeys: APPLICATION_BASIC_KEYS,
      seminarApplicationOptionKeys: APPLICATION_OPTION_KEYS,
      seminarApplicationFieldLabelKeys: APPLICATION_FIELD_LABEL_KEYS,
      seminarApplicationHeadingKeys: APPLICATION_HEADING_KEYS,
      seminarApplicationTextareaKeys: APPLICATION_TEXTAREA_KEYS,
      seminarApplicationAgreementKeys: APPLICATION_AGREEMENT_KEYS,
      navigationKeys: NAVIGATION_KEYS,
      footerKeys: FOOTER_KEYS,
      // 一般ページ用: 動的に全texts/htmlsを編集するフォールバック
      genericTexts: {},
      loginTexts: {
        page_title: '',
        page_subtitle: '',
        intro: '',
        button_login: ''
      },
      genericHtmls: {},
      // Page images (content.images)
      pageImages: [],
      showImageList: false,
      newImageKey: '',
      // Per-page media key mapping
      mediaKeys: {},
      newMediaSlot: '',
      newMediaKey: '',
      missingImageKeys: [],
      // glossary: 用語リスト（items）の編集
        glossaryItems: [],
        // faq: Q/A リストの編集
        faqItems: [],
        companyHistory: [],
      // 日本語表示用のラベルマップ（スラッグ別）
      labelMaps: {
        // トップ
        home: {
          page_title: 'ページタイトル', page_subtitle: 'ページサブタイトル',
          about_title: '研究所について（見出し）', about_subtitle: '研究所について（英字）',
          about_body: '研究所について（本文）',
          cta_secondary: 'ボタン（研究所について詳しく）'
        },
        company: {
          page_title: 'ページタイトル', page_subtitle: 'ページサブタイトル',
          nav_philosophy: 'ナビ: 経営理念', nav_message: 'ナビ: ご挨拶', nav_profile: 'ナビ: 企業概要', nav_history: 'ナビ: 沿革', nav_staff: 'ナビ: 所員紹介', nav_financial: 'ナビ: 決算報告', nav_access: 'ナビ: アクセス',
          philosophy_title: '経営理念（見出し）', philosophy_subtitle: '経営理念（英字）',
          mission_label: 'OUR MISSION（ラベル）', mission_title: 'ミッション（見出し）',
          message_title: 'ご挨拶（見出し）', message_subtitle: 'ご挨拶（英字）', message_label: 'ご挨拶（ラベル）', message_signature: 'ご挨拶（署名）',
          profile_title: '会社概要（見出し）', profile_subtitle: '会社概要（英字）',
          profile_company_name_label: '会社名（ラベル）', profile_company_name_value: '会社名（値）',
          profile_established_label: '設立（ラベル）', profile_established_value: '設立（値）',
          profile_address_label: '住所（ラベル）', profile_address_value: '住所（値・HTML可）',
          profile_representative_label: '代表者（ラベル）', profile_representative_value: '代表者（値）',
          profile_capital_label: '資本金（ラベル）', profile_capital_value: '資本金（値）',
          profile_shareholders_label: '株主（ラベル）', profile_shareholders_value: '株主（値）',
          profile_organization_label: '組織体制（ラベル）', profile_organization_value: '組織体制（値）',
          history_title: '沿革（見出し）', history_subtitle: '沿革（英字）',
          staff_title: '所員紹介（見出し）', staff_subtitle: '所員紹介（英字）',
          // Staff cards
          staff_morita_position: '森田：役職', staff_morita_name: '森田：氏名', staff_morita_reading: '森田：ふりがな', staff_morita_note: '森田：注記',
          staff_mizokami_position: '溝上：役職', staff_mizokami_name: '溝上：氏名', staff_mizokami_reading: '溝上：ふりがな',
          staff_kuga_position: '空閑：役職', staff_kuga_name: '空閑：氏名', staff_kuga_reading: '空閑：ふりがな',
          staff_takada_position: '髙田：役職', staff_takada_name: '髙田：氏名', staff_takada_reading: '髙田：ふりがな',
          staff_nakamura_position: '中村：役職', staff_nakamura_name: '中村：氏名', staff_nakamura_reading: '中村：ふりがな',
          // HTMLs
          message_body: 'ご挨拶（本文）', mission_body: 'ミッション（本文）',
          // Access
          access_title_1: 'アクセス 見出し1', access_subtitle_1: 'アクセス 英字1',
          access_phone: 'アクセス 電話', access_hours: 'アクセス 営業時間',
          access_title_2: 'アクセス 見出し2', access_station_1: 'アクセス 駅1', access_station_2: 'アクセス 駅2', access_bus_1: 'アクセス バス',
          access_title_3: 'アクセス 見出し3', access_route_1: 'アクセス ルート1', access_route_2: 'アクセス ルート2', access_route_3: 'アクセス ルート3',
          access_body_1: 'アクセス 本文1（HTML）'
        },
        'cri-consulting': {
          page_title: 'ページタイトル', page_subtitle: 'ページサブタイトル',
          what_title: '「CRI経営コンサルティングとは？」（見出し）', what_subtitle: '（英字）',
          what_content_subtitle: '説明サブ見出し', what_content_heading: '説明見出し',
          duties_title: '主な業務（見出し）', duties_subtitle: '主な業務（英字）', duties_label: '主な業務（ラベル）', duties_heading: '主な業務（説明見出し）',
          support_title: 'サポート内容と費用（見出し）', support_subtitle_en: 'サポート（英字）',
          achievements_title: '実績紹介（見出し）', achievements_subtitle: '実績紹介（英字）',
          cta_primary: 'お問い合わせボタン文言', cta_secondary: '入会ボタン文言',
          // HTMLs
          what_content_body: '説明（本文）', duties_intro: '主な業務（導入文）', duties_list: '主な業務（リストHTML）', support_intro: 'サポート（導入文）',
          // 実績（任意）
          achievements_item1_date: '実績1 日付', achievements_item1_category: '実績1 カテゴリ', achievements_item1_title: '実績1 タイトル', achievements_item1_desc: '実績1 説明（HTML）',
          achievements_item2_date: '実績2 日付', achievements_item2_category: '実績2 カテゴリ', achievements_item2_title: '実績2 タイトル', achievements_item2_desc: '実績2 説明（HTML）',
          achievements_item3_date: '実績3 日付', achievements_item3_category: '実績3 カテゴリ', achievements_item3_title: '実績3 タイトル', achievements_item3_desc: '実績3 説明（HTML）',
          achievements_item4_date: '実績4 日付', achievements_item4_category: '実績4 カテゴリ', achievements_item4_title: '実績4 タイトル', achievements_item4_desc: '実績4 説明（HTML）'
        },
        aboutus: {
          page_title: 'ページタイトル', page_subtitle: 'ページサブタイトル',
          about_title: '研究所について（見出し）', about_subtitle: '研究所について（英字）',
          service_title: 'サービス概要（見出し）', service_subtitle: 'サービス概要（英字）',
          service1_title: 'サービス1（見出し）', service1_desc: 'サービス1（説明）', service1_list: 'サービス1（リストHTML）',
          service2_title: 'サービス2（見出し）', service2_desc: 'サービス2（説明）', service2_list: 'サービス2（リストHTML）',
          service3_title: 'サービス3（見出し）', service3_desc: 'サービス3（説明）', service3_list: 'サービス3（リストHTML）',
          main_headline: 'ヒーロー見出し（HTML）',
          cta_primary: 'お問い合わせボタン文言', cta_secondary: '入会ボタン文言',
          // HTMLs
          about_body: '研究所について（本文）'
        },
        about: {
          page_title: 'ページタイトル', page_subtitle: 'ページサブタイトル',
          philosophy_title: '経営理念（見出し）', philosophy_subtitle: '経営理念（英字）',
          mission_title: 'ミッション（見出し）', mission_text: 'ミッション（本文）',
          message_title: 'ご挨拶（見出し）', message_subtitle: 'ご挨拶（英字）', message_signature: 'ご挨拶（署名）',
          company_title: '会社概要（見出し）', company_subtitle: '会社概要（英字）',
          company_label_name: '会社名（ラベル）', company_value_name: '会社名（値）',
          company_label_established: '設立（ラベル）', company_value_established: '設立（値）',
          company_label_address: '所在地（ラベル）', company_value_address: '所在地（値）',
          company_label_tel: '電話番号（ラベル）', company_value_tel: '電話番号（値）',
          company_label_business_hours: '営業時間（ラベル）', company_value_business_hours: '営業時間（値）',
          staff_title: '所属紹介（見出し）', staff_subtitle: '所属紹介（英字）',
          // HTMLs
          message_body: 'ご挨拶（本文）', company_value_address_html: '所在地（値HTML）'
        }
        ,
        // 利用規約
        terms: {
          page_title: 'ページタイトル', page_subtitle: 'ページサブタイトル',
          copyright_title: '著作権等について（見出し）', link_title: 'リンクについて（見出し）', disclaimer_title: '免責事項（見出し）',
          security_title: 'セキュリティについて（見出し）', cookie_title: 'クッキーについて（見出し）', environment_title: 'ご利用環境について（見出し）',
          prohibited_title: '禁止される行為（見出し）', article8_title: '第8条（見出し）',
          // HTMLs
          intro: '導入（本文）', copyright_body: '著作権等について（本文）', link_body: 'リンクについて（本文）', disclaimer_body: '免責事項（本文）',
          security_body: 'セキュリティについて（本文）', cookie_body: 'クッキーについて（本文）', environment_body: 'ご利用環境について（本文）',
          prohibited_body: '禁止される行為（本文）', article8_body: '第8条（本文）'
        },
        // 会員（入会案内）
        membership: {
          page_title: 'ページタイトル', page_subtitle: 'ページサブタイトル',
          intro_title: '導入（見出し）', intro_text: '導入（本文）',
          services_title: 'サービス（見出し）', services_label: 'サービス（英字）',
          premium_category_title: 'プレミアサービス（見出し）',
          premium_service1_tag: 'プレミア項目1（タグ）', premium_service1_name: 'プレミア項目1（名称）',
          standard_premium_category_title: 'スタンダード＆プレミア（見出し）',
          standard_service1_tag: '項目1（タグ）', standard_service1_name: '項目1（名称）',
          standard_service2_tag: '項目2（タグ）', standard_service2_name: '項目2（名称）',
          standard_service3_tag: '項目3（タグ）', standard_service3_name: '項目3（名称）',
          standard_service4_tag: '項目4（タグ）', standard_service4_name: '項目4（名称）',
          standard_service5_tag: '項目5（タグ）', standard_service5_name: '項目5（名称）',
          membership_info_title: '会員サービスについて（見出し）',
          membership_benefits_label: '会員のメリット（ラベル）', membership_benefits_text: '会員のメリット（本文）',
          membership_fee_label: '月会費（ラベル）', membership_fee_text: '月会費（本文）',
          flow_title: '入会までの流れ（見出し）', flow_label: '入会までの流れ（英字）',
          step1_title: 'STEP1（見出し）', step1_desc: 'STEP1（説明）',
          step2_title: 'STEP2（見出し）', step2_desc: 'STEP2（説明）',
          step3_title: 'STEP3（見出し）', step4_title: 'STEP4（見出し）',
          cta_primary: 'お問い合わせボタン文言', cta_secondary: '入会ボタン文言'
        },
        'standard-membership': {
          page_title: 'ページタイトル', page_subtitle: 'ページサブタイトル',
          intro_title: '導入（見出し）', intro_text: '導入（本文）',
          services_title: '主なサービス（見出し）', services_label: 'サービス（英字）',
          standard_category_title: 'スタンダードサービス（見出し）',
          standard_service1_tag: '項目1（タグ）', standard_service1_name: '項目1（名称）',
          standard_service2_tag: '項目2（タグ）', standard_service2_name: '項目2（名称）',
          standard_service3_tag: '項目3（タグ）', standard_service3_name: '項目3（名称）',
          standard_service4_tag: '項目4（タグ）', standard_service4_name: '項目4（名称）',
          standard_service5_tag: '項目5（タグ）', standard_service5_name: '項目5（名称）',
          membership_info_title: '会員サービスについて（見出し）',
          membership_benefits_label: '会員のメリット（ラベル）', membership_benefits_text: '会員のメリット（本文）',
          membership_fee_label: '月会費（ラベル）', membership_fee_text: '月会費（本文）',
          flow_title: '入会までの流れ（見出し）', flow_label: '入会までの流れ（英字）',
          step1_title: 'STEP1（見出し）', step1_desc: 'STEP1（説明）',
          step2_title: 'STEP2（見出し）', step2_desc: 'STEP2（説明）',
          step3_title: 'STEP3（見出し）', step4_title: 'STEP4（見出し）',
          cta_primary: 'お問い合わせボタン文言', cta_secondary: '入会ボタン文言'
        },
        'premium-membership': {
          page_title: 'ページタイトル', page_subtitle: 'ページサブタイトル',
          benefits_title: '話題の特典（見出し）', benefits_label: '話題の特典（英字）',
          featured_title: '特集（見出し）', featured_body: '特集（本文）', featured_cta: '特集CTA',
          cta_primary: 'お問い合わせボタン文言', cta_secondary: '入会ボタン文言'
        },
        sitemap: {
          intro: '導入文',
          cat_main: 'カテゴリ: メイン', cat_services: 'カテゴリ: サービス', cat_membership: 'カテゴリ: 会員', cat_support: 'カテゴリ: サポート', cat_legal: 'カテゴリ: 法務',
          link_home: 'リンク: ホーム', link_company: 'リンク: 会社概要', link_about: 'リンク: 研究所について', link_seminar: 'リンク: セミナー', link_publications: 'リンク: 刊行物', link_consulting: 'リンク: コンサルティング', link_research: 'リンク: 調査研究', link_training: 'リンク: 研修', link_membership: 'リンク: 入会案内', link_news: 'リンク: お知らせ', link_faq: 'リンク: FAQ', link_contact: 'リンク: お問い合わせ', link_privacy: 'リンク: プライバシー', link_terms: 'リンク: 利用規約', link_legal: 'リンク: 特商法'
        },
        contact: {
          page_title: 'ページタイトル', page_subtitle: 'ページサブタイトル',
          form_title: 'フォーム見出し',
          form_label_subject: 'フォーム：件名ラベル',
          form_placeholder_select: 'フォーム：プレースホルダー',
          form_option_inquiry: '件名選択肢：サービスに関するお問い合わせ',
          form_option_membership: '件名選択肢：会員に関するお問い合わせ',
          form_option_seminar: '件名選択肢：セミナーに関するお問い合わせ',
          form_option_other: '件名選択肢：その他',
          form_label_name: 'フォーム：お名前ラベル',
          form_label_kana: 'フォーム：ふりがなラベル',
          form_label_company: 'フォーム：会社名ラベル',
          form_label_position: 'フォーム：役職ラベル',
          form_label_phone: 'フォーム：電話番号ラベル',
          form_label_email: 'フォーム：メールアドレスラベル',
          form_label_content: 'フォーム：お問い合わせ内容ラベル',
          form_privacy_note: '規約案内文',
          form_privacy_agree: '規約同意チェックラベル',
          form_button_confirm: '送信ボタン文言',
          contact_title: '連絡先ブロック：タイトル',
          contact_label: '連絡先ブロック：ラベル',
          contact_subtitle: '連絡先ブロック：サブタイトル',
          contact_cta: '連絡先ブロック：ボタン文言'
        },
        glossary: {
          page_title: 'ページタイトル', page_subtitle: 'ページサブタイトル',
          intro: '導入（本文）',
          contact_title: '問合せ（見出し）', contact_subtitle: '問合せ（説明）', contact_label: '問合せ（英字）', contact_cta: '問合せボタン'
        },
        'membership-application': {
          page_title: 'ページタイトル', page_subtitle: 'ページサブタイトル',
          form_title: 'フォーム見出し',
          form_label_subject: 'フォーム：会員タイプラベル',
          form_placeholder_select: 'フォーム：プレースホルダー',
          form_option_standard: '選択肢：スタンダード会員',
          form_option_premium: '選択肢：プレミアムネット会員',
          form_option_inquiry: '選択肢：会員に関するお問い合わせ',
          form_option_other: '選択肢：その他',
          form_label_name: 'フォーム：お名前ラベル',
          form_label_kana: 'フォーム：ふりがなラベル',
        form_label_company: 'フォーム：会社名ラベル',
        form_label_position: 'フォーム：役職ラベル',
        form_label_phone: 'フォーム：電話番号ラベル',
        form_label_email: 'フォーム：メールアドレスラベル',
        form_label_content: 'フォーム：入会希望内容ラベル',
        terms_article1_title: '規約：第1条 見出し',
        terms_article1_body: '規約：第1条 本文',
        terms_article2_title: '規約：第2条 見出し',
        terms_article2_body: '規約：第2条 本文',
        terms_article3_title: '規約：第3条 見出し',
        terms_article3_intro: '規約：第3条 導入文',
          standard_title: 'スタンダード会員 見出し',
          standard_item1: 'スタンダード会員 項目①',
          standard_item2: 'スタンダード会員 項目②',
          standard_item3: 'スタンダード会員 項目③',
          standard_item4: 'スタンダード会員 項目④',
          premium_title: 'プレミアム会員 見出し',
          premium_intro: 'プレミアム会員 導入文',
          premium_item5: 'プレミアム会員 項目⑤',
          premium_item6: 'プレミアム会員 項目⑥',
          premium_item7: 'プレミアム会員 項目⑦',
          terms_download_label: '規約PDFリンク文言',
          form_privacy_note: '規約案内文',
          form_privacy_agree: '規約同意チェックラベル',
          form_button_confirm: '送信ボタン文言'
        },
        'seminar-application': {
          page_title: 'ページタイトル', page_subtitle: 'ページサブタイトル',
          form_title: 'フォーム見出し',
          form_label_subject: 'フォーム：会員タイプラベル',
          form_placeholder_select: 'フォーム：プレースホルダー',
          form_option_standard: '選択肢：スタンダード会員',
          form_option_premium: '選択肢：プレミアムネット会員',
          form_option_inquiry: '選択肢：会員に関するお問い合わせ',
          form_option_other: '選択肢：その他',
          form_label_name: 'フォーム：お名前ラベル',
          form_label_kana: 'フォーム：ふりがなラベル',
        form_label_company: 'フォーム：会社名ラベル',
        form_label_position: 'フォーム：役職ラベル',
        form_label_phone: 'フォーム：電話番号ラベル',
        form_label_email: 'フォーム：メールアドレスラベル',
        form_label_content: 'フォーム：入会希望内容ラベル',
        terms_article1_title: '規約：第1条 見出し',
        terms_article1_body: '規約：第1条 本文',
        terms_article2_title: '規約：第2条 見出し',
        terms_article2_body: '規約：第2条 本文',
        terms_article3_title: '規約：第3条 見出し',
        terms_article3_intro: '規約：第3条 導入文',
          standard_title: 'スタンダード会員 見出し',
          standard_item1: 'スタンダード会員 項目①',
          standard_item2: 'スタンダード会員 項目②',
          standard_item3: 'スタンダード会員 項目③',
          standard_item4: 'スタンダード会員 項目④',
          premium_title: 'プレミアム会員 見出し',
          premium_intro: 'プレミアム会員 導入文',
          premium_item5: 'プレミアム会員 項目⑤',
          premium_item6: 'プレミアム会員 項目⑥',
          premium_item7: 'プレミアム会員 項目⑦',
          terms_download_label: '規約PDFリンク文言',
          form_privacy_note: '規約案内文',
          form_privacy_agree: '規約同意チェックラベル',
          form_button_confirm: '送信ボタン文言'
        },
        navigation: {
          nav_about: 'ヘッダー：私たちについて',
          nav_seminars: 'ヘッダー：セミナー',
          nav_publications: 'ヘッダー：刊行物',
          nav_information: 'ヘッダー：各種情報',
          nav_membership: 'ヘッダー：入会案内',
          nav_premium_benefits: 'ヘッダー：プレミアム会員の特典',
          nav_news: 'ヘッダー：お知らせ',
          nav_company: 'ヘッダー：会社概要',
          nav_faq: 'ヘッダー：よくある質問',
          nav_indicators: 'ドロップダウン：経済指標',
          nav_statistics: 'ドロップダウン：経済・調査統計',
          nav_membership_standard: 'ドロップダウン：スタンダード会員',
          nav_membership_premium: 'ドロップダウン：プレミアム会員'
        },
        footer: {
          link_home: 'フッター：トップページ',
          link_company: 'フッター：会社概要',
          link_about: 'フッター：私たちについて',
          link_news: 'フッター：お知らせ',
          link_faq: 'フッター：よくある質問',
          link_contact: 'フッター：お問い合わせ',
          link_seminar: 'フッター：セミナー',
          link_publication: 'フッター：刊行物',
          link_glossary: 'フッター：用語集',
          link_membership: 'フッター：入会案内',
          link_membership_standard: 'フッター：スタンダード',
          link_membership_premium: 'フッター：プレミアム',
          link_cri_consulting: 'フッター：CRI経営コンサルティング',
          link_member_login: 'フッター：会員ログイン',
          link_legal: 'フッター：特定商取引法に関する表記',
          link_privacy: 'フッター：プライバシーポリシー',
          link_terms: 'フッター：利用規約',
          link_sitemap: 'フッター：サイトマップ',
          footer_postal_code: 'フッター：郵便番号',
          footer_address_line: 'フッター：住所（HTML）',
          footer_phone_label: 'フッター：電話番号（ラベル）',
          footer_phone_value: 'フッター：電話番号（値）',
          footer_hours_label: 'フッター：営業時間（ラベル）',
          footer_hours_value: 'フッター：営業時間（値）',
          footer_copyright: 'フッター：著作権表示（HTML）'
        }
      },
      // PageContent(CmsText) 側のキー。ページ選択時に推定（UIで変更可）
      pageContentKey: 'privacy',
      // editor-side media registry refs for public-like preview resolution
      _media: null,
      _pageMedia: null,
    }
  },
  mounted(){
    try {
      const pref = localStorage.getItem('cms_layout_mode')
      if (pref === null) {
        // 既定は ON
        this.layoutMode = true
      } else {
        this.layoutMode = (pref === '1' || pref === 'true')
      }
    } catch(_) { this.layoutMode = true }
    this.loadPages()
    // Ensure media registries are available for registry previews
    try { this.ensureMediaRefs() } catch(_) {}
  },
  watch: {
    layoutMode(val){
      try { localStorage.setItem('cms_layout_mode', val ? '1' : '0') } catch(_) {}
    }
  },
  computed: {
    // 除外ページ: 刊行物/お知らせ/セミナー/経済統計・指標/会員ログイン/マイページ/お問い合わせ
    excludeKeys(){
      return new Set([
        'publications','publications-public','news','notice','notices',
        'seminars','seminars-current','seminars-past',
        'economic-reports','statistics','economic-indicators'
      ])
    },
    async saveCompanyHistory(){
      try {
        const hist = Array.isArray(this.companyHistory) ? this.companyHistory
          .map((h, i) => ({
            year: String(h?.year || '').trim(),
            date: String(h?.date || '').trim(),
            body: String(h?.body || '').trim(),
            order: i,
          }))
          .filter(h => h.year || h.date || h.body) : []
        const payload = { content: { history: hist }, is_published: true }
        const res = await apiClient.adminUpdatePageContent('company-profile', payload)
        if (res && res.success) {
          alert('沿革を保存しました')
          try { localStorage.removeItem('page_content_cache:company-profile') } catch(_) {}
        } else {
          alert((res && (res.error || res.message)) || '保存に失敗しました')
        }
      } catch (e) {
        alert('保存に失敗しました')
      }
    },
    showGenericEditor(){
      const key = (this.pageContentKey || '').trim()
      if (!key) return false
      if (this.excludeKeys.has(key)) return false
      // 既に専用UIがあるものは除外
      const specialized = new Set(['privacy','terms','transaction-law','company-profile','cri-consulting','about-institute','about','membership','standard-membership','premium-membership','contact','membership-application','seminar-application','navigation','footer'])
      if (specialized.has(key)) return false
      // texts/htmls のどちらかがあるときに表示
      const hasTexts = this.genericTexts && Object.keys(this.genericTexts).length > 0
      const hasHtmls = this.genericHtmls && Object.keys(this.genericHtmls).length > 0
      return hasTexts || hasHtmls
    },
    // CMSブロックエディタの左リストから非表示にするスラッグ
    hiddenCmsSlugs(){
      return new Set([
        'company-profile',
        'membership-premium',
        'about',
        // リクエストにより非表示
        'about-institute',
        'terms-of-service',
        // 追加の非表示対象（ユーザー要望）
        'media',
        'my-account',
        'financial-reports',
        'news',
        // フロー画面（確認/完了）は編集対象外
        'contact-confirm',
        'contact-complete',
        'membership-application-confirm',
        'membership-application-complete',
        'seminar-application-confirm',
        'seminar-application-complete',
        // セミナー過去一覧ページは編集対象外
        'seminars-past',
      ])
    },
    // 表示用にフィルタ済みのページ一覧
    visiblePages(){
      try {
        const hidden = this.hiddenCmsSlugs
        return (this.pages || []).filter(p => {
          // 正規化：先頭スラッシュを除去して比較
          const slugRaw = String(p?.slug || '').toLowerCase()
          const slug = slugRaw.startsWith('/') ? slugRaw.slice(1) : slugRaw
          const meta = (p && (p.meta_json || p.meta)) || {}
          const isHiddenBySlug = hidden.has(slug)
          const isHiddenByMeta = !!(meta && (meta.hidden === true || meta.hidden === 'true'))
          return !(isHiddenBySlug || isHiddenByMeta)
        })
      } catch(_) { return this.pages || [] }
    }
  },
  methods: {
    // Backfill missing editor fields by merging texts/htmls
    // from synonymous PageContent keys for the current page.
    async hydrateMissingFromFallbacks() {
      try {
        const key = String(this.pageContentKey || '').trim()
        if (!key) return
        const candidates = this.fallbackCandidatesFor(key).filter(k => k && k !== key)
        if (!candidates.length) return

        // Helper: merge only missing or empty string values
        const mergeMissing = (target, source) => {
          if (!target || !source) return
          Object.keys(source).forEach(k => {
            const cur = target[k]
            const val = source[k]
            const isEmpty = (cur === undefined || cur === null || (typeof cur === 'string' && cur.trim() === ''))
            if (isEmpty) target[k] = val
          })
        }

        // Determine target state buckets by page kind
        const assignTexts = (texts) => {
          if (!texts) return
          switch (key) {
            case 'privacy': mergeMissing(this.privacyTexts, texts); break
            case 'terms': mergeMissing(this.termsTexts, texts); break
            case 'transaction-law': mergeMissing(this.tlTexts, texts); break
            case 'company-profile': mergeMissing(this.companyTexts, texts); break
            case 'cri-consulting': mergeMissing(this.consultingTexts, texts); break
            case 'about-institute': mergeMissing(this.aboutTexts, texts); break
            case 'about': mergeMissing(this.aboutTexts, texts); break
            case 'membership': mergeMissing(this.membershipTexts, texts); break
            case 'standard-membership': mergeMissing(this.standardTexts, texts); break
            case 'premium-membership': mergeMissing(this.premiumTexts, texts); break
            case 'contact': mergeMissing(this.contactTexts, texts); break
            case 'membership-application': mergeMissing(this.membershipApplicationTexts, texts); break
            case 'seminar-application': mergeMissing(this.seminarApplicationTexts, texts); break
            case 'navigation': mergeMissing(this.navigationTexts, texts); break
            case 'footer': mergeMissing(this.footerTexts, texts); break
            case 'faq': mergeMissing(this.genericTexts, texts); break
            case 'glossary': mergeMissing(this.genericTexts, texts); break
            default: mergeMissing(this.genericTexts, texts); break
          }
        }
        const assignHtmls = (htmls) => {
          if (!htmls) return
          switch (key) {
            case 'terms': mergeMissing(this.termsHtmls, htmls); break
            case 'transaction-law': mergeMissing(this.tlHtmls, htmls); break
            case 'company-profile': mergeMissing(this.companyHtmls, htmls); break
            case 'cri-consulting': mergeMissing(this.consultingHtmls, htmls); break
            case 'about-institute': mergeMissing(this.aboutHtmls, htmls); break
            case 'about': mergeMissing(this.aboutHtmls, htmls); break
            case 'contact': mergeMissing(this.contactHtmls, htmls); break
            default: mergeMissing(this.genericHtmls, htmls); break
          }
        }

        for (const k of candidates) {
          try {
            const res = await apiClient.adminGetPageContent(k)
            const content = res?.data?.page?.content || {}
            const texts = (content && typeof content === 'object' && content.texts && typeof content.texts === 'object') ? content.texts : null
            const htmls = (content && typeof content === 'object' && content.htmls && typeof content.htmls === 'object') ? content.htmls : null
            // Merge into current state if fields are missing
            assignTexts(texts)
            assignHtmls(htmls)
          } catch (_) { /* try next */ }
        }
      } catch (_) { /* silent backfill */ }
    },
    // Define synonym keys per logical page to search for backfill
    fallbackCandidatesFor(key) {
      const k = String(key || '').toLowerCase()
      switch (k) {
        case 'privacy':
          return ['privacy', 'privacy-policy', 'privacy-poricy', 'privacy poricy']
        case 'terms':
          return ['terms', 'terms-of-service']
        case 'transaction-law':
          return ['transaction-law', 'legal', 'commercial-law']
        case 'company-profile':
          return ['company-profile', 'company']
        case 'cri-consulting':
          return ['cri-consulting', 'consulting']
        case 'about-institute':
          return ['about-institute', 'aboutus', 'about']
        case 'about':
          return ['about', 'aboutus', 'about-institute']
        case 'membership':
          return ['membership', 'services']
        case 'standard-membership':
          return ['standard-membership']
        case 'premium-membership':
          return ['premium-membership']
        case 'contact':
          return ['contact']
        case 'membership-application':
          return ['membership-application']
        case 'seminar-application':
          return ['seminar-application']
        case 'navigation':
          return ['navigation']
        case 'footer':
          return ['footer']
        case 'faq':
          return ['faq']
        case 'glossary':
          return ['glossary']
        case 'home':
          return ['home']
        default:
          return [k]
      }
    },
    getRegistryImageUrl(key){
      try {
        const m = this._media
        if (m && typeof m.getResponsiveImage === 'function') return m.getResponsiveImage(key, '') || ''
        if (m && typeof m.getImage === 'function') return m.getImage(key, '') || ''
      } catch(_) {}
      return ''
    },
    triggerRegistryUpload(key){
      const refName = `img_registry_${key}`
      const el = this.$refs[refName]
      if (el && el[0] && typeof el[0].click === 'function') el[0].click()
      else if (el && typeof el.click === 'function') el.click()
    },
    async onReplaceRegistryImage(key, e){
      try{
        const file = (e.target.files && e.target.files[0]) || null
        if (!file) return
        const res = await apiClient.adminReplacePageImage('media', key, file)
        if (res && res.success !== false){
          // Invalidate and reload media registry so preview updates
          try {
            const mod = await import('@/composables/useMedia')
            if (mod.invalidateMediaCache) mod.invalidateMediaCache()
            try { localStorage.removeItem('cms_media_cache'); localStorage.setItem('cms_media_cache_bust', String(Date.now())) } catch(_) {}
            try { this.ensureMediaRefs() } catch(_) {}
          } catch(_) {}
          alert('画像を差し替えました')
        } else {
          alert('差し替えに失敗しました')
        }
      } catch(_) { alert('差し替えに失敗しました') }
    },
    async ensureMediaRefs(){
      try {
        if (!this._media) {
          const mod = await import('@/composables/useMedia')
          const { useMedia } = mod
          this._media = useMedia()
          try { this._media.ensure() } catch(_) {}
        }
        if (this.pageContentKey) {
          const modPM = await import('@/composables/usePageMedia')
          const { usePageMedia } = modPM
          if (!this._pageMedia) this._pageMedia = usePageMedia()
          try { await this._pageMedia.ensure(this.pageContentKey) } catch(_) {}
        }
      } catch(_) { /* ignore */ }
    },
    defaultMediaKeyForSlot(slot){
      try {
        const key = String(slot||'')
        if (key === 'hero') {
          const p = String(this.pageContentKey||'').replace(/-/g,'_')
          return p ? `hero_${p}` : 'hero'
        }
        return key
      } catch(_) { return slot }
    },
    // preview helpers removed
    async loadPages(){
      const res = await apiClient.listCmsPages({ search: this.search, per_page: 100, exclude_hidden: 1 })
      if (res.success) this.pages = res.data.data || []
      // Enhance titles with current PageContent page_title where available
      try { await this.applyPrettyTitles() } catch(_) {}
      // auto-select by route param or query
      const slug = this.$route.params.slug || this.$route.query.slug
      if (slug) {
        const match = (this.pages || []).find(p => p.slug === slug)
        // 非表示対象は自動選択しない
        if (match && !this.hiddenCmsSlugs.has(String(match.slug || '').toLowerCase())) { await this.selectPage(match) }
        else {
          // If not exists, prefill create modal with slug
          this.createForm.slug = slug
          this.createForm.title = slug
          this.showCreate = true
        }
      }
    },
    displayLabel(key, isHtml=false){
      const slug = (this.currentPage && this.currentPage.slug) || ''
      // services ページは membership と同じラベルマップを使用
      const effective = slug === 'services' ? 'membership' : slug
      const map = this.labelMaps[effective] || {}
      const base = map[key] || key
      if (isHtml && !/（HTML）$/.test(base)) return `${base}（HTML）`
      return base
    },
    pageContentKeyFromSlug(slug){
      const s = (slug||'').toLowerCase()
      // Flow pages (confirm/complete) → 既存のPageContentにブリッジ
      if (s.startsWith('contact-') && (s.endsWith('-confirm') || s.endsWith('-complete'))) return 'contact'
      if (s.startsWith('membership-application-') && (s.endsWith('-confirm') || s.endsWith('-complete'))) return 'membership-application'
      if (s.startsWith('seminar-application-') && (s.endsWith('-confirm') || s.endsWith('-complete'))) return 'seminar-application'
      // Seminars pages
      if (s === 'seminars' || s.startsWith('seminars/')) return 'seminars'
      if (s.includes('seminars-current')) return 'seminars-current'
      if (s.includes('seminars-past')) return 'seminars-past'
      if (s.includes('privacy')) return 'privacy'
      if (s.includes('legal') || s.includes('transaction')) return 'transaction-law'
      if (s.includes('terms')) return 'terms'
      if (s.includes('economic-indicators')) return 'economic-indicators'
      if (s.includes('economic-statistics')) return 'economic-statistics'
      if (s.includes('publications')) return 'publications'
      if (s.includes('company')) return 'company-profile'
      if (s.includes('consult')) return 'cri-consulting'
      if (s.includes('aboutus')) return 'about-institute'
      if (s.includes('about')) return 'about'
      if (s.includes('sitemap')) return 'sitemap'
      if (s.includes('faq')) return 'faq'
      if (s.includes('glossary')) return 'glossary'
      if (s.includes('membership-application')) return 'membership-application'
      if (s.includes('seminar-application')) return 'seminar-application'
      if (s.includes('premium') && s.includes('membership')) return 'premium-membership'
      if (s.includes('standard') && s.includes('membership')) return 'standard-membership'
      if (s.includes('membership')) return 'membership'
      if (s.includes('navigation')) return 'navigation'
      if (s.includes('footer')) return 'footer'
      if (s.includes('contact')) return 'contact'
      if (s.includes('login')) return 'login'
      if (s === 'home') return 'home'
      // '/services' uses MembershipPage content
      if (s.includes('services')) return 'membership'
      return null
    },
    async applyPrettyTitles(){
      const list = Array.isArray(this.pages)? this.pages : []
      const jobs = list.map(async (p)=>{
        const key = this.pageContentKeyFromSlug(p.slug)
        if (!key) return
        try{
          const r = await apiClient.adminGetPageContent(key)
          const page = r?.data?.page
          if (!page) return
          const texts = page?.content?.texts || {}
          const title = (typeof texts.page_title === 'string' && texts.page_title.trim()) ? texts.page_title.trim() : (page.title || '')
          if (title) p.title = title
        }catch(_){ /* ignore */ }
      })
      await Promise.all(jobs)
    },
    async selectPage(p){
      const res = await apiClient.getCmsPage(p.id)
      if (res.success){
        this.currentPage = res.data
        // very small mapping to two demo sections: hero (sort 10) and rich (sort 20)
        const secs = (res.data.sections||[])
        const hero = secs.find(s=>s.sort===10) || { id: 'hero', sort: 10, component_type:'Hero', props_json:{ title: '' } }
        const rich = secs.find(s=>s.sort===20) || { id: 'rich', sort: 20, component_type:'RichText', props_json:{ html: '' } }
        this.hero = { title: (hero.props_json&&hero.props_json.title)||'' }
        this.richText = { html: (rich.props_json&&rich.props_json.html)||'' }
        this.collectWarnings([hero, rich])
        // 推奨のPageContentキーを推定
        const slug = (this.currentPage.slug || '').toLowerCase()
        if (slug.includes('privacy')) this.pageContentKey = 'privacy'
        else if (slug.includes('legal') || slug.includes('transaction')) this.pageContentKey = 'transaction-law'
        else if (slug.includes('terms')) this.pageContentKey = 'terms'
        else if (slug.includes('company')) this.pageContentKey = 'company-profile'
        else if (slug.includes('economic-indicators')) this.pageContentKey = 'economic-indicators'
        else if (slug.includes('economic-statistics')) this.pageContentKey = 'economic-statistics'
        else if (slug.includes('publications')) this.pageContentKey = 'publications'
        else if (slug.includes('consult')) this.pageContentKey = 'cri-consulting'
        else if (slug.includes('aboutus')) this.pageContentKey = 'about-institute'
        else if (slug.includes('about')) this.pageContentKey = 'about'
        else if (slug.includes('sitemap')) this.pageContentKey = 'sitemap'
        else if (slug.includes('faq')) this.pageContentKey = 'faq'
        else if (slug.includes('glossary')) this.pageContentKey = 'glossary'
        else if (slug.includes('membership-application')) this.pageContentKey = 'membership-application'
        else if (slug.includes('seminar-application')) this.pageContentKey = 'seminar-application'
        else if (slug.includes('premium') && slug.includes('membership')) this.pageContentKey = 'premium-membership'
        else if (slug.includes('standard') && slug.includes('membership')) this.pageContentKey = 'standard-membership'
        else if (slug.includes('membership')) this.pageContentKey = 'membership'
        else if (slug.includes('navigation')) this.pageContentKey = 'navigation'
        else if (slug.includes('footer')) this.pageContentKey = 'footer'
        else if (slug.startsWith('contact-') && (slug.endsWith('-confirm') || slug.endsWith('-complete'))) this.pageContentKey = 'contact'
        else if (slug.includes('contact')) this.pageContentKey = 'contact'
        else if (slug.includes('login')) this.pageContentKey = 'login'
        else if (slug === 'home') this.pageContentKey = 'home'
        else if (slug.includes('services')) this.pageContentKey = 'membership'
        else if (slug.includes('seminars-current')) this.pageContentKey = 'seminars-current'
        else if (slug.includes('seminars-past')) this.pageContentKey = 'seminars-past'
        else if (slug === 'seminars') this.pageContentKey = 'seminars'
        // consulting / services ページは既定ONだが、ユーザ保存がある場合は尊重
        try {
          const pref = localStorage.getItem('cms_layout_mode')
          if (pref === null) {
            if ((slug||'').includes('consult') || (slug||'').includes('services')) this.layoutMode = true
          }
        } catch(_) {}
        // プレビュー機能は撤去

        // 既存テキストの読み込み
        try {
          // 切替時に汎用フィールドを初期化（前ページのキーが残らないように）
          this.genericTexts = {}
          this.genericHtmls = {}
        const page = await apiClient.adminGetPageContent(this.pageContentKey)
        const content = page?.data?.page?.content || {}
          const texts = (content && typeof content === 'object' && content.texts && typeof content.texts === 'object') ? content.texts : {}
        const htmls = (content && typeof content === 'object' && content.htmls && typeof content.htmls === 'object') ? content.htmls : {}
        // images map → pageImages
        const imgs = (content && typeof content === 'object' && content.images && typeof content.images === 'object') ? content.images : {}
        this.pageImages = Object.keys(imgs).map(k => {
          const v = imgs[k]
          return { key: k, url: (typeof v === 'string') ? v : (v?.url || ''), filename: (typeof v === 'object' ? (v.filename || '') : '') }
        })
        const mkeys = (content && typeof content === 'object' && content.media_keys && typeof content.media_keys === 'object') ? content.media_keys : {}
        this.mediaKeys = { ...mkeys }
        this.missingImageKeys = this.calculateMissingImages()
          // Reset minimal headings
          if (this.pageContentKey === 'privacy') {
            // 既存のprivacyTextsにAPIの全キーをマージ
            this.privacyTexts = { ...(this.privacyTexts || {}), ...(texts || {}) }
            if (!this.privacyTexts.page_title) this.privacyTexts.page_title = this.currentPage.title || ''
          } else if (this.pageContentKey === 'terms') {
            // terms: texts/htmls ともに全キーをそのままマージ
            this.termsTexts = { ...(this.termsTexts || {}), ...(texts || {}) }
            this.termsHtmls = { ...(this.termsHtmls || {}), ...(htmls || {}) }
            if (!this.termsTexts.page_title) this.termsTexts.page_title = this.currentPage.title || ''
          } else if (this.pageContentKey === 'transaction-law') {
            // 特商法: texts/htmls ともに全キーをそのままマージ
            this.tlTexts = { ...(this.tlTexts || {}), ...(texts || {}) }
            this.tlHtmls = { ...(this.tlHtmls || {}), ...(htmls || {}) }
            if (!this.tlTexts.page_title) this.tlTexts.page_title = this.currentPage.title || ''
          } else if (this.pageContentKey === 'company-profile') {
            this.companyTexts = { ...(this.companyTexts || {}), ...(texts || {}) }
            this.companyHtmls = { ...(this.companyHtmls || {}), ...(htmls || {}) }
            // history
            this.companyHistory = Array.isArray(content?.history) ? content.history.map(h => ({
              year: typeof h?.year === 'string' ? h.year : '',
              date: typeof h?.date === 'string' ? h.date : '',
              body: typeof h?.body === 'string' ? h.body : (typeof h?.title === 'string' ? h.title : '')
            })) : []
            // financial_reports
            this.companyFinancialReports = Array.isArray(content?.financial_reports) ? content.financial_reports.map((r) => ({
              fiscal_year: typeof r?.fiscal_year === 'string' ? r.fiscal_year : '',
              date_label: typeof r?.date_label === 'string' ? r.date_label : '',
              items: Array.isArray(r?.items) ? r.items.map(it => ({
                label: typeof it?.label === 'string' ? it.label : (typeof it === 'string' ? it : ''),
                url: typeof it?.url === 'string' ? it.url : ''
              })) : []
            })) : []
            // staff（images/order は content.staff、氏名等は texts.staff_* をプレフィル）
            const staffRaw = Array.isArray(content?.staff) ? content.staff : []
            if (staffRaw.length) {
              const textsMap = (content && typeof content === 'object' && content.texts && typeof content.texts === 'object') ? content.texts : {}
              const read = (key, fallback='') => {
                try { const v = textsMap[key]; return (typeof v === 'string' && v.length) ? v : fallback } catch(_) { return fallback }
              }
              const legacyMap = new Map();
              try { Array.isArray(COMPANY_STAFF_LEGACY) && COMPANY_STAFF_LEGACY.forEach(e => legacyMap.set(e.id, e)) } catch(_) {}
              const enriched = staffRaw.map((m, i) => {
                const id = (m && typeof m.id === 'string') ? m.id : `staff-${i+1}`
                const meta = legacyMap.get(id)
                const name = (m && m.name) || (meta ? read(meta.nameKey, meta.name||'') : '')
                const reading = (m && m.reading) || (meta ? read(meta.readingKey, meta.reading||'') : '')
                const position = (m && m.position) || (meta ? read(meta.positionKey, meta.position||'') : '')
                const note = (m && m.note) || (meta ? read(meta.noteKey, meta.note||'') : '')
                const alt = (m && m.alt) || name || ''
                return { ...m, id, name, reading, position, note, alt }
              })
              this.companyStaff = this.normalizeCompanyStaffCollection(enriched)
            } else {
              // texts のみからも復元
              this.companyStaff = this.defaultCompanyStaff()
            }
            if (!this.companyStaff.length) this.companyStaff = [this.normalizeCompanyStaffMember({}, 0)]
            // 初回は既存（静的）テキストをデフォルトとして投入
            if (!this.companyFinancialReports || this.companyFinancialReports.length === 0) {
              this.companyFinancialReports = this.defaultCompanyFinancialReports()
            }
            if (!this.companyTexts.page_title) this.companyTexts.page_title = this.currentPage.title || ''
            if (!this.companyTexts.financial_reports_title) this.companyTexts.financial_reports_title = '決算報告'
            if (!this.companyTexts.financial_reports_subtitle) this.companyTexts.financial_reports_subtitle = 'financial reports'
            this.missingImageKeys = this.calculateMissingImages()
          } else if (this.pageContentKey === 'cri-consulting') {
            this.consultingTexts = { ...(this.consultingTexts || {}), ...(texts || {}) }
            this.consultingHtmls = { ...(this.consultingHtmls || {}), ...(htmls || {}) }
            if (!this.consultingTexts.page_title) this.consultingTexts.page_title = this.currentPage.title || ''
          } else if (this.pageContentKey === 'about-institute') {
            this.aboutTexts = { ...(this.aboutTexts || {}), ...(texts || {}) }
            this.aboutHtmls = { ...(this.aboutHtmls || {}), ...(htmls || {}) }
            if (!this.aboutTexts.page_title) this.aboutTexts.page_title = this.currentPage.title || ''
          } else if (this.pageContentKey === 'about') {
            this.aboutTexts = { ...(this.aboutTexts || {}), ...(texts || {}) }
            this.aboutHtmls = { ...(this.aboutHtmls || {}), ...(htmls || {}) }
            if (!this.aboutTexts.page_title) this.aboutTexts.page_title = this.currentPage.title || ''
          } else if (this.pageContentKey === 'membership') {
            this.membershipTexts = { ...(this.membershipTexts || {}), ...(texts || {}) }
          } else if (this.pageContentKey === 'standard-membership') {
            this.standardTexts = { ...(this.standardTexts || {}), ...(texts || {}) }
          } else if (this.pageContentKey === 'premium-membership') {
            this.premiumTexts = { ...(this.premiumTexts || {}), ...(texts || {}) }
          } else if (this.pageContentKey === 'contact') {
            this.contactTexts = { ...(this.contactTexts || {}), ...(texts || {}) }
            this.contactHtmls = { ...(this.contactHtmls || {}), ...(htmls || {}) }
          } else if (this.pageContentKey === 'membership-application') {
            this.membershipApplicationTexts = { ...(this.membershipApplicationTexts || {}), ...(texts || {}) }
            if (!this.membershipApplicationTexts.page_title) this.membershipApplicationTexts.page_title = this.currentPage.title || ''
          } else if (this.pageContentKey === 'seminar-application') {
            this.seminarApplicationTexts = { ...(this.seminarApplicationTexts || {}), ...(texts || {}) }
            if (!this.seminarApplicationTexts.page_title) this.seminarApplicationTexts.page_title = this.currentPage.title || ''
          } else if (this.pageContentKey === 'navigation') {
            this.navigationTexts = { ...(this.navigationTexts || {}), ...(texts || {}) }
            if (!this.navigationTexts.page_title) this.navigationTexts.page_title = this.currentPage.title || ''
          } else if (this.pageContentKey === 'footer') {
            this.footerTexts = { ...(this.footerTexts || {}), ...(texts || {}) }
            if (!this.footerTexts.page_title) this.footerTexts.page_title = this.currentPage.title || ''
          } else if (this.pageContentKey === 'glossary') {
            // Glossary: texts/htmls はそのまま、items をロード
            this.genericTexts = {}
            this.genericHtmls = {}
            this.glossaryItems = Array.isArray(content?.items) ? content.items.map(it => ({
              term: typeof it?.term === 'string' ? it.term : (typeof it?.title === 'string' ? it.title : ''),
              category: typeof it?.category === 'string' ? it.category : '',
              definition: typeof it?.definition === 'string' ? it.definition : (typeof it?.content === 'string' ? it.content : '')
            })) : []
          } else if (this.pageContentKey === 'faq') {
            // FAQ: textsはカテゴリ名など。faqs 配列をロード
            this.genericTexts = { ...(texts || {}) }
            this.genericHtmls = { ...(htmls || {}) }
            const list = Array.isArray(content?.faqs) ? content.faqs : []
            this.faqItems = list.map((it, idx) => ({
              category: typeof it?.category === 'string' ? it.category : 'all',
              question: typeof it?.question === 'string' ? it.question : '',
              answer: typeof it?.answer === 'string' ? it.answer : '',
              tags: Array.isArray(it?.tags) ? it.tags : [],
              _id: (typeof it?._id === 'number' || typeof it?._id === 'string') ? it._id : idx
            }))
          } else {
            // フォールバック: 任意ページの全texts/htmlsを編集（置き換え）
            this.genericTexts = { ...(texts || {}) }
            this.genericHtmls = { ...(htmls || {}) }
          }
        } catch(_) { /* noop */ }
        // After initial load, attempt to backfill any blank fields from synonymous PageContent keys
        try { await this.hydrateMissingFromFallbacks() } catch(_) {}
      }
    },
    triggerReplace(idx){
      const r = this.$refs[`replace_${idx}`]
      if (r && r[0] && typeof r[0].click === 'function') r[0].click()
      else if (r && typeof r.click === 'function') r.click()
    },
    async onReplacePageImage(idx, e){
      try {
        const file = (e.target.files && e.target.files[0]) || null
        if (!file) return
        const item = this.pageImages[idx]
        if (!item || !this.pageContentKey) return
        const res = await apiClient.adminReplacePageImage(this.pageContentKey, item.key, file)
        if (res && res.success !== false) {
          // Bridge: mirror into media registry for company_profile_* keys used by useMedia()
          try {
            if (String(item.key).startsWith('company_profile_')) {
              await apiClient.adminReplacePageImage('media', item.key, file)
            }
          } catch(_) { /* ignore */ }
          // Invalidate media cache so the latest images are used immediately
          try {
            const mod = await import('@/composables/useMedia')
            if (mod.invalidateMediaCache) mod.invalidateMediaCache()
            try {
              localStorage.removeItem('cms_media_cache')
              localStorage.setItem('cms_media_cache_bust', String(Date.now()))
            } catch(_) {}
            try {
              if (mod.useMedia) { const m = mod.useMedia(); if (m && typeof m.ensure === 'function') m.ensure() }
            } catch(_) { /* ignore */ }
          } catch(_) { /* ignore */ }
          const page = await this.refreshPageImages()
          // Notify public pages to reload PageContent cache immediately
          try {
            await this.writePageContentCache(page)
          } catch(_) { /* ignore */ }
          alert('画像を差し替えました')
        } else {
          alert('差し替えに失敗しました')
        }
      } catch(_) { alert('差し替えに失敗しました') }
    },
    triggerAddNew(){
      if (!this.newImageKey) { alert('新規キーを入力してください'); return }
      if (this.$refs.newImageInput && typeof this.$refs.newImageInput.click === 'function') this.$refs.newImageInput.click()
    },
    async onAddNewPageImage(e){
      try {
        const file = (e.target.files && e.target.files[0]) || null
        if (!file || !this.newImageKey || !this.pageContentKey) return
        // 同じファイル名での再選択でも change が発火するように、値を即クリア
        try { e.target.value = '' } catch(_) {}
        const res = await apiClient.adminReplacePageImage(this.pageContentKey, this.newImageKey, file)
        if (res && res.success !== false) {
          // Bridge to media registry when applicable
          try {
            if (String(this.newImageKey).startsWith('company_profile_')) {
              await apiClient.adminReplacePageImage('media', this.newImageKey, file)
            }
          } catch(_) { /* ignore */ }
          // Invalidate media cache after adding new image key
          try {
            const mod = await import('@/composables/useMedia')
            if (mod.invalidateMediaCache) mod.invalidateMediaCache()
            try {
              localStorage.removeItem('cms_media_cache')
              localStorage.setItem('cms_media_cache_bust', String(Date.now()))
            } catch(_) {}
            try { if (mod.useMedia) { const m = mod.useMedia(); if (m && typeof m.ensure === 'function') m.ensure() } } catch(_) {}
          } catch(_) { /* ignore */ }
          const page = await this.refreshPageImages()
          this.newImageKey = ''
          // Broadcast page content cache update to live pages
          try {
            await this.writePageContentCache(page)
          } catch(_) { /* ignore */ }
          alert('画像を追加しました')
        } else {
          alert('追加に失敗しました')
        }
      } catch(_) { alert('追加に失敗しました') }
    },
    async writePageContentCache(page){
      const key = String(this.pageContentKey || '').trim()
      if (!key) return
      let target = page
      if (!target) {
        target = await this.fetchPageContent(key)
      }
      try {
        if (target && typeof target === 'object') {
          localStorage.setItem('page_content_cache:' + key, JSON.stringify(target))
          try {
            window.dispatchEvent(new CustomEvent('cms-media-updated', {
              detail: { pageKey: key, reason: 'page-content-cache-write' }
            }))
          } catch(_) {}
          try {
            const evt = new StorageEvent('storage', {
              key: 'page_content_cache:' + key,
              newValue: localStorage.getItem('page_content_cache:' + key),
              storageArea: localStorage
            })
            window.dispatchEvent(evt)
          } catch(_) {
            try { window.dispatchEvent(new Event('page-content-cache-write')) } catch(_) {}
          }
          // Warm the browser cache so switching src renders instantly
          try { this.prefetchPageImages(target) } catch(_) {}
        }
      } catch(_) {
        try { localStorage.setItem('page_content_cache:' + key, String(Date.now())) } catch(_) {}
        try {
          window.dispatchEvent(new CustomEvent('cms-media-updated', {
            detail: { pageKey: key, reason: 'page-content-cache-fallback' }
          }))
        } catch(_) {}
        try {
          const evt = new StorageEvent('storage', {
            key: 'page_content_cache:' + key,
            newValue: localStorage.getItem('page_content_cache:' + key),
            storageArea: localStorage
          })
          window.dispatchEvent(evt)
        } catch(_) {
          try { window.dispatchEvent(new Event('page-content-cache-fallback')) } catch(_) {}
        }
      }
    },
    prefetchPageImages(page){
      const imgs = (page && page.content && page.content.images) || {}
      const urls = []
      const add = (u) => { if (typeof u === 'string' && u.length) urls.push(u) }
      for (const k of Object.keys(imgs)){
        const v = imgs[k]
        if (typeof v === 'string') add(v)
        else if (v && typeof v === 'object' && v.url){
          let u = String(v.url)
          try {
            if (u.indexOf('/storage/') !== -1 && v.uploaded_at){
              const ver = Date.parse(v.uploaded_at) || null
              if (ver !== null) u += (u.includes('?') ? '&' : '?') + '_t=' + encodeURIComponent(String(ver))
            }
          } catch(_) {}
          add(u)
        }
        if (urls.length >= 8) break
      }
      // Kick off background fetches
      try { urls.forEach(u => { const i = new Image(); i.decoding = 'async'; i.loading = 'eager'; i.src = u }) } catch(_) {}
    },
    async fetchPageContent(key = this.pageContentKey){
      try {
        const res = await apiClient.adminGetPageContent(key)
        return res?.data?.page || null
      } catch(_) { return null }
    },
    collectWarnings(sections){
      const warn = []
      const expected = {
        'Hero': new Set(['title']),
        'RichText': new Set(['html'])
      }
      for (const s of sections){
        if (!s || !s.component_type) continue
        const props = (s.props_json && typeof s.props_json === 'object') ? Object.keys(s.props_json) : []
        const ex = expected[s.component_type]
        if (ex){
          for (const k of props){ if (!ex.has(k)) warn.push(`${s.component_type}: 未対応キー「${k}」`) }
        } else {
          warn.push(`未対応ブロック: ${s.component_type}`)
        }
      }
      this.warnings = warn
    },
    renderPreviewHtml(){
      const h = this.hero.title ? `<section><h1>${this.escape(this.hero.title)}</h1></section>` : ''
      const r = this.richText.html ? `<section>${this.richText.html}</section>` : ''
      return `<!doctype html><html><head><meta charset='utf-8'><style>body{font-family:sans-serif;padding:16px;}section{margin:16px 0;}h1{font-size:24px;}</style></head><body>${h}${r}</body></html>`
    },
    escape(s){ return (s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;') },
    async savePageMeta(){ if (!this.currentPage) return; await apiClient.updateCmsPage(this.currentPage.id, { title: this.currentPage.title }) },
    async saveHero(){ if (!this.currentPage) return; await apiClient.upsertCmsSection(this.currentPage.id, 'hero', { sort:10, component_type:'Hero', props_json:{ title: this.hero.title }, status:'draft' }) },
    async saveRich(){ if (!this.currentPage) return; await apiClient.upsertCmsSection(this.currentPage.id, 'rich', { sort:20, component_type:'RichText', props_json:{ html: this.richText.html }, status:'draft' }) },
    // Financial reports editor actions
    addCompanyReport(){
      if (!Array.isArray(this.companyFinancialReports)) this.companyFinancialReports = []
      this.companyFinancialReports.push({ fiscal_year: '', date_label: '', items: [] })
    },
    addCompanyReportItem(ri){
      const r = (this.companyFinancialReports || [])[ri]
      if (!r) return
      if (!Array.isArray(r.items)) r.items = []
      r.items.push({ label: '', url: '' })
    },
    removeCompanyReportItem(ri, ii){
      const r = (this.companyFinancialReports || [])[ri]
      if (!r || !Array.isArray(r.items)) return
      r.items.splice(ii, 1)
    },
    async uploadCompanyReportItemPdf(ri, ii){
      try {
        const input = document.createElement('input')
        input.type = 'file'
        input.accept = 'application/pdf'
        input.onchange = async (e) => {
          const file = e.target.files[0]
          if (!file) return
          const res = await apiClient.uploadMedia(file, { directory: 'public/media/uploads' })
          const url = res?.file?.url || res?.data?.file?.url || res?.data?.url || ''
          if (url) {
            const r = (this.companyFinancialReports || [])[ri]
            if (r && Array.isArray(r.items) && r.items[ii]) { r.items[ii].url = url }
            alert('PDFをアップロードしました')
          } else {
            alert(res?.message || 'アップロードに失敗しました')
          }
        }
        input.click()
      } catch(_) {
        alert('アップロードに失敗しました')
      }
    },
    defaultCompanyFinancialReports(){
      // CompanyProfile.vue の既存ダミー表示を初期値として反映（URLは空で作成）
      return [
        {
          fiscal_year: '2025年3月期',
          date_label: '決算情報（2025年5月12日）',
          items: [
            { label: '決算情報（2025年5月12日）', url: '' },
            { label: '決算要旨（PDF ： 1.28MB／全31ページ）', url: '' },
            { label: '決算説明会 第Ⅰ部（決算報告）資料（PDF ： 384KB／全22ページ）', url: '' },
            { label: '決算説明会 第Ⅱ部（社長メッセージ）スピーチ（PDF ： 620KB／全11ページ）', url: '' },
            { label: 'メディア向け決算説明会の模様をご覧いただけます', url: '' },
          ]
        },
        {
          fiscal_year: '2024年3月期',
          date_label: '決算情報（2024年5月12日）',
          items: [
            { label: '決算情報（2024年5月12日）', url: '' },
            { label: '決算要旨（PDF ： 1.25MB／全30ページ）', url: '' },
            { label: '決算説明会 第Ⅰ部（決算報告）資料（PDF ： 380KB／全21ページ）', url: '' },
            { label: '決算説明会 第Ⅱ部（社長メッセージ）スピーチ（PDF ： 615KB／全10ページ）', url: '' },
            { label: 'メディア向け決算説明会の模様をご覧いただけます', url: '' },
          ]
        },
        {
          fiscal_year: '2023年3月期',
          date_label: '決算情報（2023年5月12日）',
          items: [
            { label: '決算情報（2023年5月12日）', url: '' },
            { label: '決算要旨（PDF ： 1.22MB／全29ページ）', url: '' },
            { label: '決算説明会 第Ⅰ部（決算報告）資料（PDF ： 375KB／全20ページ）', url: '' },
            { label: '決算説明会 第Ⅱ部（社長メッセージ）スピーチ（PDF ： 610KB／全9ページ）', url: '' },
            { label: 'メディア向け決算説明会の模様をご覧いただけます', url: '' },
          ]
        },
        {
          fiscal_year: '2022年3月期',
          date_label: '決算情報（2022年5月12日）',
          items: [
            { label: '決算情報（2022年5月12日）', url: '' },
            { label: '決算要旨（PDF ： 1.20MB／全28ページ）', url: '' },
            { label: '決算説明会 第Ⅰ部（決算報告）資料（PDF ： 370KB／全19ページ）', url: '' },
            { label: '決算説明会 第Ⅱ部（社長メッセージ）スピーチ（PDF ： 605KB／全8ページ）', url: '' },
            { label: 'メディア向け決算説明会の模様をご覧いただけます', url: '' },
          ]
        }
      ]
    },
    createStaffLocalId(index = 0){
      return `staff-${Date.now()}-${Math.random().toString(36).slice(2)}-${index}`
    },
    normalizeCompanyStaffMember(member = {}, index = 0){
      const imageObj = (member && typeof member.image === 'object') ? member.image : null
      const imageKey = typeof member?.image_key === 'string' ? member.image_key : (typeof member?.imageKey === 'string' ? member.imageKey : '')
      const imageUrl = typeof member?.image_url === 'string' ? member.image_url : (typeof member?.imageUrl === 'string' ? member.imageUrl : (imageObj?.url || ''))
      return {
        __localId: member.__localId || this.createStaffLocalId(index),
        id: typeof member?.id === 'string' ? member.id : (typeof member?.slug === 'string' ? member.slug : ''),
        name: typeof member?.name === 'string' ? member.name : '',
        reading: typeof member?.reading === 'string' ? member.reading : '',
        position: typeof member?.position === 'string' ? member.position : '',
        note: typeof member?.note === 'string' ? member.note : '',
        alt: typeof member?.alt === 'string' ? member.alt : (typeof member?.name === 'string' ? member.name : ''),
        image_key: imageKey,
        image_url: imageUrl,
        image: imageObj ? {
          url: imageObj.url || '',
          path: imageObj.path || '',
          filename: imageObj.filename || ''
        } : null,
      }
    },
    normalizeCompanyStaffCollection(list){
      return Array.isArray(list)
        ? list.map((member, index) => this.normalizeCompanyStaffMember(member, index))
        : []
    },
    buildCompanyStaffFromLegacyTexts(){
      try {
        return COMPANY_STAFF_LEGACY.map((entry, index) => this.normalizeCompanyStaffMember({
          id: entry.id,
          name: (this.companyTexts && this.companyTexts[entry.nameKey]) || entry.name || '',
          reading: (this.companyTexts && this.companyTexts[entry.readingKey]) || entry.reading || '',
          position: (this.companyTexts && this.companyTexts[entry.positionKey]) || entry.position || '',
          note: (this.companyTexts && this.companyTexts[entry.noteKey]) || entry.note || '',
          image_key: entry.imageKey,
          image_url: this.getImageUrlByKey(entry.imageKey) || entry.fallbackImage || '',
          alt: (this.companyTexts && this.companyTexts[entry.nameKey]) || entry.name || '',
        }, index))
      } catch(_) {
        return []
      }
    },
    defaultCompanyStaff(){
      return this.buildCompanyStaffFromLegacyTexts()
    },
    resolveCompanyStaffPreview(member){
      if (!member) return TRANSPARENT_PIXEL
      const direct = member.image_url && member.image_url.trim()
      if (direct) return direct
      const fromKey = member.image_key ? this.getImageUrlByKey(member.image_key) : ''
      if (fromKey) return fromKey
      if (member.image && typeof member.image === 'object' && member.image.url) return member.image.url
      const legacy = COMPANY_STAFF_LEGACY.find(it => it.imageKey === member.image_key || it.id === member.id)
      if (legacy && legacy.fallbackImage) return legacy.fallbackImage
      return TRANSPARENT_PIXEL
    },
    handleCompanyStaffImageUpload(member){
      if (!member || !member.image_key) {
        alert('先に画像キーを入力してください')
        return
      }
      this.uploadForKey(member.image_key)
    },
    clearCompanyStaffImageUrl(member){
      if (!member) return
      member.image_url = ''
      this.missingImageKeys = this.calculateMissingImages()
    },
    onCompanyStaffImageKeyChange(member){
      if (!member) return
      if (member.image_key) member.image_key = member.image_key.trim()
      this.missingImageKeys = this.calculateMissingImages()
    },
    addCompanyStaff(){
      if (!Array.isArray(this.companyStaff)) this.companyStaff = []
      const index = this.companyStaff.length
      this.companyStaff.push(this.normalizeCompanyStaffMember({
        id: '',
        name: '',
        reading: '',
        position: '',
        note: '',
        image_key: '',
        image_url: '',
      }, index))
      this.missingImageKeys = this.calculateMissingImages()
    },
    removeCompanyStaff(index){
      if (!Array.isArray(this.companyStaff)) return
      this.companyStaff.splice(index, 1)
      this.companyStaff = this.normalizeCompanyStaffCollection(this.companyStaff)
      this.missingImageKeys = this.calculateMissingImages()
    },
    moveCompanyStaff(index, delta){
      if (!Array.isArray(this.companyStaff)) return
      const target = index + delta
      if (target < 0 || target >= this.companyStaff.length) return
      const clone = [...this.companyStaff]
      const [item] = clone.splice(index, 1)
      clone.splice(target, 0, item)
      this.companyStaff = this.normalizeCompanyStaffCollection(clone)
      this.missingImageKeys = this.calculateMissingImages()
    },
    async resetCompanyStaffFromLegacy(){
      try {
        // 破棄確認
        if (typeof window !== 'undefined') {
          const ok = window.confirm('未保存の変更は失われます。保存済みデータ（または旧テキスト）を読み込みますか？')
          if (!ok) return
        }
        // 1) まずは保存済みの PageContent.content.staff を優先して読み込み
        let loaded = []
        try {
          const res = await apiClient.adminGetPageContent('company-profile')
          const page = (res && (res.page || res.data?.page)) || null
          let staff = page && page.content && Array.isArray(page.content.staff) ? page.content.staff : []
          if (staff.length) {
            const hasOrder = staff.some(x => typeof x?.order === 'number')
            if (hasOrder) staff = [...staff].sort((a,b)=>{
              const ao = (typeof a?.order === 'number') ? a.order : Number.MAX_SAFE_INTEGER
              const bo = (typeof b?.order === 'number') ? b.order : Number.MAX_SAFE_INTEGER
              return ao - bo
            })
            loaded = staff.map((m, i) => this.normalizeCompanyStaffMember(m, i)).filter(Boolean)
          }
        } catch(_) { /* fallback to legacy texts */ }

        // 2) 保存済みがなければ、旧テキストから再構築
        if (!loaded.length) {
          try {
            loaded = this.buildCompanyStaffFromLegacyTexts().map((m, i) => this.normalizeCompanyStaffMember(m, i)).filter(Boolean)
          } catch(_) { /* keep empty */ }
        }

        // 3) どちらも無ければ既定
        if (!loaded.length) loaded = this.defaultCompanyStaff()
        if (!loaded.length) loaded = [this.normalizeCompanyStaffMember({}, 0)]

        this.companyStaff = loaded
        this.missingImageKeys = this.calculateMissingImages()
      } catch(_) {
        // 最後の手段
        this.companyStaff = this.defaultCompanyStaff()
        if (!this.companyStaff.length) this.companyStaff = [this.normalizeCompanyStaffMember({}, 0)]
        this.missingImageKeys = this.calculateMissingImages()
      }
    },
    sanitizeCompanyStaffMember(member, index){
      const normalized = this.normalizeCompanyStaffMember(member, index)
      const trimmedName = (normalized.name || '').trim()
      const trimmedPosition = (normalized.position || '').trim()
      const trimmedNote = (normalized.note || '').trim()
      const trimmedImageKey = (normalized.image_key || '').trim()
      const trimmedImageUrl = (normalized.image_url || '').trim()
      const hasContent = trimmedName || trimmedPosition || trimmedNote || trimmedImageKey || trimmedImageUrl
      if (!hasContent) return null
      const payload = {
        id: (normalized.id || `staff-${index + 1}`).trim(),
        order: index,
        name: trimmedName,
        reading: (normalized.reading || '').trim(),
        position: trimmedPosition,
        note: trimmedNote,
      }
      if (normalized.alt && normalized.alt.trim() && normalized.alt.trim() !== payload.name) {
        payload.alt = normalized.alt.trim()
      }
      if (trimmedImageKey) payload.image_key = trimmedImageKey
      if (trimmedImageUrl) payload.image_url = trimmedImageUrl
      if (normalized.image && (normalized.image.url || normalized.image.path || normalized.image.filename)) {
        payload.image = {
          url: normalized.image.url || '',
          path: normalized.image.path || '',
          filename: normalized.image.filename || ''
        }
      }
      return payload
    },
    async saveCompanyFinancialReports(){
      try {
        const reports = Array.isArray(this.companyFinancialReports) ? this.companyFinancialReports.map(r => ({
          fiscal_year: String(r?.fiscal_year || '').trim(),
          date_label: String(r?.date_label || '').trim(),
          items: Array.isArray(r?.items) ? r.items.map(it => ({
            label: String((it && it.label) || (typeof it === 'string' ? it : '') || '').trim(),
            url: String((it && it.url) || '').trim()
          })).filter(it => it.label || it.url) : []
        })).filter(r => r.fiscal_year || r.date_label || (r.items && r.items.length)) : []
        const payload = { content: { financial_reports: reports }, is_published: true }
        const res = await apiClient.adminUpdatePageContent('company-profile', payload)
        if (res && res.success) {
          alert('決算報告を保存しました')
        } else {
          const msg = (res && (res.error || res.message)) || '保存に失敗しました（認証切れの可能性あり）'
          alert(msg)
        }
      } catch (e) {
        alert('保存に失敗しました')
      }
    },
    // スタッフのテキストのみ保存（texts に反映）
    async saveCompanyStaffTexts(){
      try {
        const texts = this.buildStaffTextsFromUi()
        if (!texts || !Object.keys(texts).length) {
          alert('保存対象のテキストがありません')
          return
        }
        const payload = { content: { texts }, is_published: true }
        const res = await apiClient.adminUpdatePageContent('company-profile', payload)
        if (res && res.success) {
          alert('所員テキストを保存しました')
          try { localStorage.removeItem('page_content_cache:company-profile') } catch(_) {}
        } else {
          const msg = (res && (res.error || res.message)) || '保存に失敗しました（認証切れの可能性あり）'
          alert(msg)
        }
      } catch(e) { alert('保存に失敗しました') }
    },

    // スタッフの画像・順序のみ保存（content.staff に反映）
    async saveCompanyStaffVisuals(){
      try {
        const visuals = Array.isArray(this.companyStaff) ? this.companyStaff.map((m, i) => {
          const id = (m && m.id) ? String(m.id).trim() : `staff-${i+1}`
          const out = { id, order: i }
          const k = (m && m.image_key) ? String(m.image_key).trim() : ''
          const u = (m && m.image_url) ? String(m.image_url).trim() : ''
          if (k) out.image_key = k
          if (u) out.image_url = u
          if (m && m.image && (m.image.url || m.image.path || m.image.filename)) {
            out.image = {
              url: m.image.url || '', path: m.image.path || '', filename: m.image.filename || ''
            }
          }
          // alt は任意
          if (m && m.alt && String(m.alt).trim() && String(m.alt).trim() !== String(m.name||'')) out.alt = String(m.alt).trim()
          return out
        }).filter(Boolean) : []

        const payload = { content: { staff: visuals }, is_published: true }
        const res = await apiClient.adminUpdatePageContent('company-profile', payload)
        if (res && res.success) {
          alert('所員の画像・順序を保存しました')
          try { localStorage.removeItem('page_content_cache:company-profile') } catch(_) {}
        } else {
          const msg = (res && (res.error || res.message)) || '保存に失敗しました（認証切れの可能性あり）'
          alert(msg)
        }
      } catch(e) { alert('保存に失敗しました') }
    },

    // 統合保存: テキスト＋画像＋順序を content.staff に一本化（textsは互換ミラー）
    async saveCompanyStaffUnified(){
      try {
        const staff = Array.isArray(this.companyStaff)
          ? this.companyStaff.map((m, i) => this.sanitizeCompanyStaffMember(m, i)).filter(Boolean)
          : []
        const payload = { content: { staff }, is_published: true }
        // texts へミラー（互換用）
        try {
          const tx = this.buildStaffTextsFromUi()
          if (tx && Object.keys(tx).length) {
            payload.content.texts = { ...(payload.content.texts || {}), ...tx }
          }
        } catch(_) { /* noop */ }
        const res = await apiClient.adminUpdatePageContent('company-profile', payload)
        if (res && res.success) {
          alert('所員（統合）を保存しました')
          try { localStorage.removeItem('page_content_cache:company-profile') } catch(_) {}
        } else {
          const msg = (res && (res.error || res.message)) || '保存に失敗しました（認証切れの可能性あり）'
          alert(msg)
        }
      } catch(e) { alert('保存に失敗しました') }
    },

    buildStaffTextsFromUi(){
      try {
        const legacyMap = new Map()
        COMPANY_STAFF_LEGACY.forEach(e => legacyMap.set(e.id, e))
        const fromUi = Array.isArray(this.companyStaff) ? this.companyStaff : []
        const acc = {}
        fromUi.forEach((m, i) => {
          const id = (m && m.id) ? String(m.id) : `staff-${i+1}`
          const meta = legacyMap.get(id)
          if (!meta) return
          if (meta.nameKey) acc[meta.nameKey] = String(m?.name || '').trim()
          if (meta.readingKey) acc[meta.readingKey] = String(m?.reading || '').trim()
          if (meta.positionKey) acc[meta.positionKey] = String(m?.position || '').trim()
          if (meta.noteKey) acc[meta.noteKey] = String(m?.note || '').trim()
        })
        // 動的キーも付与（staff_<id>_name 等）: 新規IDでもテキストが反映されるように
        fromUi.forEach((m, i) => {
          const id = (m && m.id) ? String(m.id) : `staff-${i+1}`
          const prefix = `staff_${id}_`
          const name = String(m?.name || '').trim()
          const reading = String(m?.reading || '').trim()
          const position = String(m?.position || '').trim()
          const note = String(m?.note || '').trim()
          if (name) acc[`${prefix}name`] = name
          if (reading) acc[`${prefix}reading`] = reading
          if (position) acc[`${prefix}position`] = position
          if (note) acc[`${prefix}note`] = note
        })
        // 見出し・英字も併せて保存
        if (this.companyTexts) {
          if (this.companyTexts.staff_title !== undefined) acc['staff_title'] = String(this.companyTexts.staff_title||'').trim()
          if (this.companyTexts.staff_subtitle !== undefined) acc['staff_subtitle'] = String(this.companyTexts.staff_subtitle||'').trim()
        }
        return acc
      } catch(_) { return {} }
    },
    async syncLegacyPageContentIfApplicable(){
      try {
        if (!this.currentPage) return
        const slug = (this.currentPage.slug || '').toLowerCase()
        const inferredKey = this.pageContentKey || this.pageContentKeyFromSlug(slug)
        if (!inferredKey) return
        const built = this.buildLegacyPageContentPayload(inferredKey)
        if (!built) return
        await apiClient.adminUpdatePageContent(built.pageKey, built.payload)
      } catch (_) { /* non-blocking bridge */ }
    },
    async publish(){
      if (!this.currentPage) return
      // Bridge legacy PageContent before publishing v2 (non-blocking)
      try { await this.syncLegacyPageContentIfApplicable() } catch(_) {}
      const res = await apiClient.publishCmsPage(this.currentPage.id)
      if (res.success) {
        try { await apiClient.setCmsOverride({ slug: this.currentPage.slug, page_id: this.currentPage.id, enabled: true }) } catch(_){ /* ignore */ }
        alert('公開しました（オーバーライドON）')
      }
    },
    async unpublish(){
      if (!this.currentPage) return
      try {
        const res = await apiClient.setCmsOverride({ slug: this.currentPage.slug, page_id: this.currentPage.id, enabled: false })
        if (res.success) alert('公開を停止しました（オーバーライドOFF）')
      } catch(_){ alert('公開停止に失敗しました') }
    },
    async issuePreview(){
      if (!this.currentPage) return
      try{
        const tok = await apiClient.issueCmsPreviewToken(this.currentPage.id)
        if (tok.success) {
          const t = tok.data.token
          const slug = tok.data.slug
          this.previewUrl = getApiUrl(`/api/public/pages-v2/${encodeURIComponent(slug)}/preview?token=${encodeURIComponent(t)}`)
          alert('プレビューリンクを作成しました。開くボタンで確認できます。')
        }
      }catch(_){ alert('プレビューリンクの作成に失敗しました') }
    },
    selectContentImage(){ this.$refs.contentImgInput && this.$refs.contentImgInput.click() },
    async onContentImageSelected(e){
      const f = (e.target.files && e.target.files[0]) || null
      if (!f || !this.currentPage) return
      try{
        const up = await apiClient.uploadCmsMedia(f)
        if (up && up.success){
          const id = up.data.id
          const mime = (up.data.mime||'').toLowerCase()
          const ext = mime.includes('png')? 'png' : mime.includes('webp')? 'webp' : mime.includes('gif')? 'gif' : 'jpg'
          const url = getApiUrl(`/api/public/m/${encodeURIComponent(id)}/md.${encodeURIComponent(ext)}`)
          this.lastContentImgUrl = url
        } else {
          alert('画像アップロードに失敗しました')
        }
      } catch(_){ alert('画像アップロードに失敗しました') }
    },
    getImageUrlByKey(key){
      try {
        const item = (this.pageImages || []).find(it => it.key === key)
        const direct = item ? (item.url || '') : ''
        if (direct && !this.isPlaceholderUrl(direct)) return direct
        // Try per-page mapping and global media like the public pages do
        try {
          const mk = (this.mediaKeys && this.mediaKeys[key]) ? this.mediaKeys[key] : this.defaultMediaKeyForSlot(key)
          if (this._pageMedia && typeof this._pageMedia.getResponsiveSlot === 'function') {
            const slot = this._pageMedia.getResponsiveSlot(key, mk, '')
            if (slot) return slot
          }
          if (this._media) {
            if (typeof this._media.getResponsiveImage === 'function') {
              const v = this._media.getResponsiveImage(mk, '')
              if (v) return v
            }
            if (typeof this._media.getImage === 'function') {
              const v = this._media.getImage(mk, '')
              if (v) return v
            }
          }
        } catch(_) {}
        return direct || ''
      } catch(_) { return '' }
    },
    expectedImageKeys(){
      const key = this.pageContentKey || ''
      if (key === 'company-profile') {
        const baseKeys = [
          'hero',
          'company_profile_philosophy',
          'company_profile_message',
        ]
        const staffKeys = Array.isArray(this.companyStaff)
          ? this.companyStaff.map(member => member && member.image_key).filter(k => typeof k === 'string' && k.length)
          : []
        const fallbackStaffKeys = COMPANY_STAFF_LEGACY.map(item => item.imageKey)
        const keySet = new Set(baseKeys)
        if (staffKeys.length) {
          staffKeys.forEach(k => keySet.add(k))
        } else {
          fallbackStaffKeys.forEach(k => keySet.add(k))
        }
        return Array.from(keySet)
      }
      if (key === 'about' || key === 'about-institute') {
        return [
          'hero','content','message',
          // About Institute specific
          'about_image','service1_image','service2_image','service3_image'
        ]
      }
      if (key === 'cri-consulting') {
        return [
          'hero',
          'what_image',
          'duties_image',
          // Support section images (FREE/PAID)
          'support_free_consultation',
          'support_free_problem',
          'support_paid_strategy',
          'support_paid_improvement',
          'support_paid_analysis',
          'support_paid_newbiz',
          'achievements_item1_image',
          'achievements_item2_image',
          'achievements_item3_image',
          'achievements_item4_image',
        ]
      }
      if (key === 'membership' || key === 'standard-membership' || key === 'premium-membership') {
        const base = [
          'hero',
          'premium_service1_image',
          'standard_service1_image',
          'standard_service2_image',
          'standard_service3_image',
          'standard_service4_image',
          'standard_service5_image',
        ]
        if (key === 'premium-membership') base.push('featured_image')
        return base
      }
      if (key === 'home') {
        return [
          'hero_slide_1','hero_slide_2','hero_slide_3',
          'banner_seminar','banner_publications','banner_info','banner_membership',
          'about_card1_image','about_card2_image','about_card3_image',
          'home_partner_logo1','home_partner_logo2','home_partner_logo3','home_partner_logo4'
        ]
      }
      if (key === 'contact') {
        return [
          'contact_section_bg'
        ]
      }
      if (key === 'login') {
        return ['hero']
      }
      if (key === 'privacy' || key === 'terms' || key === 'transaction-law') {
        return ['hero']
      }
      if (key === 'economic-indicators' || key === 'economic-statistics' || key === 'publications') {
        return ['hero']
      }
      if (key === 'faq' || key === 'glossary' || key === 'sitemap') {
        return ['hero']
      }
      return []
    },
    isPlaceholderUrl(url){
      if (!url) return true
      const u = String(url)
      // Only treat obvious placeholders as such
      if (u.includes('hero-image.png')) return true
      if (u.includes('TEMP/')) return true // builder temporary CDN marker
      if (/Image_fx\d+\.jpg/i.test(u)) return true // legacy static hero placeholders
      // Local static assets like /img/image-*.png or /img/---*.png are valid
      return false
    },
    calculateMissingImages(){
      try {
        const expected = this.expectedImageKeys()
        if (!expected || expected.length === 0) return []
        const map = {}
        for (const it of (this.pageImages||[])) { map[it.key] = it.url || '' }
        const missing = []
        for (const k of expected) {
          const url = map[k]
          if (!url || this.isPlaceholderUrl(url)) missing.push(k)
        }
        return missing
      } catch(_) { return [] }
    },
    uploadForKey(key){
      try {
        const idx = (this.pageImages||[]).findIndex(it => it.key === key)
        if (idx >= 0) {
          this.triggerReplace(idx)
        } else {
          this.newImageKey = key
          // 一覧表示がオフのときでも動作させる（グローバル用 hidden input をフォールバック）
          if (this.$refs.newImageInput && typeof this.$refs.newImageInput.click === 'function') {
            this.$refs.newImageInput.click()
          } else if (this.$refs.newImageInputGlobal && typeof this.$refs.newImageInputGlobal.click === 'function') {
            this.$refs.newImageInputGlobal.click()
          } else if (this.$refs.newImageInputAny && typeof this.$refs.newImageInputAny.click === 'function') {
            this.$refs.newImageInputAny.click()
          }
        }
      } catch(_) {}
    },
    triggerCompanyImageUpload(key){
      const refName = `img_${key}`
      const el = this.$refs[refName]
      if (el && el[0] && typeof el[0].click === 'function') el[0].click()
      else if (el && typeof el.click === 'function') el.click()
    },
    triggerHomeSlideUpload(key){
      this.triggerCompanyImageUpload(key)
    },
    async onCompanyImageSelected(key, e){
      try {
        const file = (e.target.files && e.target.files[0]) || null
        if (!file || !this.pageContentKey) return
        const res = await apiClient.adminReplacePageImage(this.pageContentKey, key, file)
        if (res && res.success !== false) {
          // Bridge: also mirror to media registry for company_profile_* keys
          try {
            if (String(key).startsWith('company_profile_')) {
              await apiClient.adminReplacePageImage('media', key, file)
            }
          } catch(_) { /* ignore */ }
          // Invalidate media cache after replacing company-specific image
          try {
            const mod = await import('@/composables/useMedia')
            if (mod.invalidateMediaCache) mod.invalidateMediaCache()
            try {
              localStorage.removeItem('cms_media_cache')
              localStorage.setItem('cms_media_cache_bust', String(Date.now()))
            } catch(_) {}
            try { if (mod.useMedia) { const m = mod.useMedia(); if (m && typeof m.ensure === 'function') m.ensure() } } catch(_) {}
          } catch(_) { /* ignore */ }
          const page = await this.refreshPageImages()

          // Ensure legacy PageContent is published so public API reflects the change
          try {
            await apiClient.adminUpdatePageContent(this.pageContentKey, { is_published: true })
          } catch(_) { /* non-blocking */ }

          // Notify other tabs (public page) to reload page content cache immediately
          try {
            await this.writePageContentCache(page)
          } catch(_) { /* ignore */ }
        } else {
          alert('画像アップロードに失敗しました')
        }
      } catch(_) {
        alert('画像アップロードに失敗しました')
      }
    },
    async onHomeSlideSelected(key, e){
      // 同じ実装（homeページの content.images.<key> へ保存）
      return this.onCompanyImageSelected(key, e)
    },
    async refreshPageImages(){
      try {
        const res = await apiClient.adminGetPageContent(this.pageContentKey)
        const page = res?.data?.page || null
        const content = page?.content || {}
        const imgs = (content && typeof content === 'object' && content.images && typeof content.images === 'object') ? content.images : {}
        this.pageImages = Object.keys(imgs).map(k => {
          const v = imgs[k]
          return { key: k, url: (typeof v === 'string') ? v : (v?.url || ''), filename: (typeof v === 'object' ? (v.filename || '') : '') }
        })
        const mkeys = (content && typeof content === 'object' && content.media_keys && typeof content.media_keys === 'object') ? content.media_keys : {}
        this.mediaKeys = { ...mkeys }
        this.missingImageKeys = this.calculateMissingImages()
        // Prepare media registries for editor-side fallback resolution
        await this.ensureMediaRefs()
        return page
      } catch(_) { return null }
    },
    insertLastContentImage(){
      if (!this.lastContentImgUrl) return
      const html = this.richText.html || ''
      this.richText.html = `${html}\n<p><img src="${this.lastContentImgUrl}" alt=""></p>`
      this.lastContentImgUrl = ''
    },
    async importExistingPrivacy(){
      try {
        // 候補キーを順に探索（UIで指定→ privacy → privacy-poricy → privacy-policy → privacy poricy）
        const candidates = [
          this.pageContentKey,
          // common fallbacks
          'terms', 'transaction-law', 'company-profile', 'consulting', 'about-institute',
          'about', 'sitemap', 'faq', 'glossary', 'membership', 'home', 'services',
          'privacy', 'privacy-poricy', 'privacy-policy', 'privacy poricy'
        ]
        let foundKey = null
        let res = null
        for (const k of candidates) {
          try {
            const r = await apiClient.adminGetPageContent(k)
            if (r && r.success && r.data && r.data.page) { res = r; foundKey = k; break }
          } catch(_) { /* try next */ }
        }
        // 無ければ既定キーで初期作成
        if (!res || !foundKey) {
          foundKey = this.pageContentKey
          const defaultTitle = foundKey === 'terms' ? '利用規約' : (foundKey === 'transaction-law' ? '特定商取引法に関する表記' : 'プライバシーポリシー')
          const defaultTexts = foundKey === 'terms' ? { page_title: '利用規約', page_subtitle: 'terms of service' } : (foundKey === 'transaction-law' ? { page_title: '特定商取引法に関する表記', page_subtitle: 'transaction law' } : { page_title: 'プライバシーポリシー', page_subtitle: 'privacy policy', intro: '' })
          await apiClient.post('/api/admin/pages', {
            page_key: foundKey,
            title: defaultTitle,
            content: { html: '', texts: defaultTexts },
            is_published: true
          })
          res = await apiClient.adminGetPageContent(foundKey)
        }
        // 採用キーを記録
        this.pageContentKey = foundKey
        const content = res?.data?.page?.content || {}
        const texts = content.texts || {}
        // Prefer content.html; fallback to content.htmls.body if html is empty
        const htmls = (content && content.htmls) || {}
        const bodyHtml = (typeof htmls?.body === 'string') ? htmls.body : ''
        const html = (typeof content?.html === 'string' && content.html.trim()) ? content.html : bodyHtml
        if (typeof html === 'string') {
          this.richText.html = html
        }
        // set known fields if present (per page)
        if (foundKey === 'privacy') {
          this.privacyTexts = { ...(this.privacyTexts || {}), ...(texts || {}) }
        } else if (foundKey === 'terms') {
          this.termsTexts = { ...(this.termsTexts || {}), ...(texts || {}) }
          this.termsHtmls = { ...(this.termsHtmls || {}), ...(htmls || {}) }
        } else if (foundKey === 'transaction-law') {
          this.tlTexts = { ...(this.tlTexts || {}), ...(texts || {}) }
          this.tlHtmls = { ...(this.tlHtmls || {}), ...(htmls || {}) }
        } else if (foundKey === 'company-profile') {
          this.companyTexts = { ...(this.companyTexts || {}), ...(texts || {}) }
          this.companyHtmls = { ...(this.companyHtmls || {}), ...(htmls || {}) }
          this.companyHistory = Array.isArray(content?.history) ? content.history.map(h => ({
            year: typeof h?.year === 'string' ? h.year : '',
            date: typeof h?.date === 'string' ? h.date : '',
            body: typeof h?.body === 'string' ? h.body : (typeof h?.title === 'string' ? h.title : '')
          })) : []
        } else if (foundKey === 'cri-consulting') {
          this.consultingTexts = { ...(this.consultingTexts || {}), ...(texts || {}) }
          this.consultingHtmls = { ...(this.consultingHtmls || {}), ...(htmls || {}) }
        } else if (foundKey === 'about-institute') {
          this.aboutTexts = { ...(this.aboutTexts || {}), ...(texts || {}) }
          this.aboutHtmls = { ...(this.aboutHtmls || {}), ...(htmls || {}) }
        } else if (foundKey === 'glossary') {
          this.glossaryItems = Array.isArray(content?.items) ? content.items.map(it => ({
            term: typeof it?.term === 'string' ? it.term : (typeof it?.title === 'string' ? it.title : ''),
            category: typeof it?.category === 'string' ? it.category : '',
            definition: typeof it?.definition === 'string' ? it.definition : (typeof it?.content === 'string' ? it.content : '')
          })) : []
        } else if (foundKey === 'faq') {
          this.genericTexts = { ...(texts || {}) }
          this.genericHtmls = { ...(htmls || {}) }
          const list = Array.isArray(content?.faqs) ? content.faqs : []
          this.faqItems = list.map((it, idx) => ({
            category: typeof it?.category === 'string' ? it.category : 'all',
            question: typeof it?.question === 'string' ? it.question : '',
            answer: typeof it?.answer === 'string' ? it.answer : '',
            tags: Array.isArray(it?.tags) ? it.tags : [],
            _id: (typeof it?._id === 'number' || typeof it?._id === 'string') ? it._id : idx
          }))
        } else {
          // generic: 置き換え
          this.genericTexts = { ...(texts || {}) }
          this.genericHtmls = { ...(htmls || {}) }
        }
        alert('既存の文言を取り込みました')
      } catch (e) {
        console.warn('importExistingPrivacy failed', e)
        alert('取り込みに失敗しました')
      }
    },
    async syncRichToPageContentHtml(){
      try {
        const html = this.richText.html || ''
        // Sync to both html and htmls.body for backward compatibility
        const patch = { content: { html, htmls: { body: html } }, is_published: true }
        if (this.mediaKeys && Object.keys(this.mediaKeys).length) patch.content.media_keys = { ...this.mediaKeys }
        const res = await apiClient.adminUpdatePageContent(this.pageContentKey, patch)
        if (res) alert('PageContentに本文を同期しました')
      } catch (e) {
        alert('同期に失敗しました')
      }
    },
    buildLegacyPageContentPayload(pageKeyOverride = null){
      const key = pageKeyOverride || this.pageContentKey
      if (!key) return null
      const patch = { content: {} }
      const hasEntries = (obj) => !!(obj && Object.keys(obj).length)

      if (key === 'privacy') {
        if (hasEntries(this.privacyTexts)) patch.content.texts = { ...this.privacyTexts }
      } else if (key === 'terms') {
        if (hasEntries(this.termsTexts)) patch.content.texts = { ...this.termsTexts }
        if (hasEntries(this.termsHtmls)) patch.content.htmls = { ...this.termsHtmls }
      } else if (key === 'transaction-law') {
        if (hasEntries(this.tlTexts)) patch.content.texts = { ...this.tlTexts }
        if (hasEntries(this.tlHtmls)) patch.content.htmls = { ...this.tlHtmls }
      } else if (key === 'company-profile') {
        if (hasEntries(this.companyTexts)) patch.content.texts = { ...this.companyTexts }
        if (hasEntries(this.companyHtmls)) patch.content.htmls = { ...this.companyHtmls }

        // Build staff visuals (画像・順序のみ)
        const visuals = Array.isArray(this.companyStaff) ? this.companyStaff.map((m, i) => {
          const id = (m && m.id) ? String(m.id).trim() : `staff-${i+1}`
          const out = { id, order: i }
          const k = (m && m.image_key) ? String(m.image_key).trim() : ''
          const u = (m && m.image_url) ? String(m.image_url).trim() : ''
          if (k) out.image_key = k
          if (u) out.image_url = u
          if (m && m.image && (m.image.url || m.image.path || m.image.filename)) {
            out.image = { url: m.image.url || '', path: m.image.path || '', filename: m.image.filename || '' }
          }
          if (m && m.alt && String(m.alt).trim() && String(m.alt).trim() !== String(m.name||'')) out.alt = String(m.alt).trim()
          return out
        }).filter(Boolean) : []
        patch.content.staff = visuals

        // staff テキストは buildStaffTextsFromUi で texts にミラー（公開側が texts 優先のため）
        try {
          const tx = this.buildStaffTextsFromUi()
          if (tx && Object.keys(tx).length) {
            if (!patch.content.texts) patch.content.texts = {}
            patch.content.texts = { ...patch.content.texts, ...tx }
          }
        } catch(_) { /* noop */ }

        const hist = Array.isArray(this.companyHistory) ? this.companyHistory
          .map(h => ({ year: String(h?.year || '').trim(), date: String(h?.date || '').trim(), body: String(h?.body || '').trim() }))
          .filter(h => h.year || h.date || h.body) : []
        patch.content.history = hist
        const reports = Array.isArray(this.companyFinancialReports) ? this.companyFinancialReports.map(r => ({
          fiscal_year: String(r?.fiscal_year || '').trim(),
          date_label: String(r?.date_label || '').trim(),
          items: Array.isArray(r?.items) ? r.items.map(it => ({
            label: String((it && it.label) || (typeof it === 'string' ? it : '') || '').trim(),
            url: String((it && it.url) || '').trim()
          })).filter(it => it.label || it.url) : []
        })).filter(r => r.fiscal_year || r.date_label || (r.items && r.items.length)) : []
        if (reports.length) patch.content.financial_reports = reports
      } else if (key === 'cri-consulting') {
        if (hasEntries(this.consultingTexts)) patch.content.texts = { ...this.consultingTexts }
        if (hasEntries(this.consultingHtmls)) patch.content.htmls = { ...this.consultingHtmls }
      } else if (key === 'about-institute') {
        if (hasEntries(this.aboutTexts)) patch.content.texts = { ...this.aboutTexts }
        if (hasEntries(this.aboutHtmls)) patch.content.htmls = { ...this.aboutHtmls }
      } else if (key === 'about') {
        const aboutSanitized = { ...(this.aboutTexts || {}) }
        try {
          Object.keys(aboutSanitized).forEach(k => {
            if (/^staff\d+_(title|name)$/.test(k)) delete aboutSanitized[k]
            if (k === 'staff_title' || k === 'staff_subtitle') delete aboutSanitized[k]
          })
        } catch (_) {}
        if (hasEntries(aboutSanitized)) patch.content.texts = { ...aboutSanitized }
        if (hasEntries(this.aboutHtmls)) patch.content.htmls = { ...this.aboutHtmls }
      } else if (key === 'glossary') {
        if (hasEntries(this.genericTexts)) patch.content.texts = { ...this.genericTexts }
        if (hasEntries(this.genericHtmls)) patch.content.htmls = { ...this.genericHtmls }
        const items = Array.isArray(this.glossaryItems) ? this.glossaryItems
          .map(it => ({
            term: String(it?.term || '').trim(),
            category: String(it?.category || '').trim(),
            definition: String(it?.definition || '').trim()
          }))
          .filter(it => it.term && it.definition) : []
        patch.content.items = items
      } else if (key === 'faq') {
        if (hasEntries(this.genericTexts)) patch.content.texts = { ...this.genericTexts }
        if (hasEntries(this.genericHtmls)) patch.content.htmls = { ...this.genericHtmls }
        const faqs = Array.isArray(this.faqItems) ? this.faqItems.map((it, idx) => ({
          category: String((it?.category || 'all')).trim() || 'all',
          question: String(it?.question || '').trim(),
          answer: String(it?.answer || '').trim(),
          tags: Array.isArray(it?.tags) ? it.tags.map(t => String(t).trim()).filter(Boolean) : [],
          _id: (typeof it?._id === 'number' || typeof it?._id === 'string') ? it._id : idx
        })).filter(it => it.question || it.answer) : []
        patch.content.faqs = faqs
      } else if (key === 'membership') {
        if (hasEntries(this.membershipTexts)) patch.content.texts = { ...this.membershipTexts }
      } else if (key === 'standard-membership') {
        if (hasEntries(this.standardTexts)) patch.content.texts = { ...this.standardTexts }
      } else if (key === 'premium-membership') {
        if (hasEntries(this.premiumTexts)) patch.content.texts = { ...this.premiumTexts }
      } else if (key === 'contact') {
        if (hasEntries(this.contactTexts)) patch.content.texts = { ...this.contactTexts }
        if (hasEntries(this.contactHtmls)) patch.content.htmls = { ...this.contactHtmls }
      } else if (key === 'membership-application') {
        if (hasEntries(this.membershipApplicationTexts)) patch.content.texts = { ...this.membershipApplicationTexts }
      } else if (key === 'seminar-application') {
        if (hasEntries(this.seminarApplicationTexts)) patch.content.texts = { ...this.seminarApplicationTexts }
      } else if (key === 'login') {
        if (hasEntries(this.loginTexts)) patch.content.texts = { ...this.loginTexts }
      } else if (key === 'navigation') {
        if (hasEntries(this.navigationTexts)) patch.content.texts = { ...this.navigationTexts }
      } else if (key === 'footer') {
        if (hasEntries(this.footerTexts)) patch.content.texts = { ...this.footerTexts }
      } else {
        const hasGeneric = (hasEntries(this.genericTexts) || hasEntries(this.genericHtmls))
        if (hasEntries(this.genericTexts)) patch.content.texts = { ...this.genericTexts }
        if (hasEntries(this.genericHtmls)) patch.content.htmls = { ...this.genericHtmls }
        if (!hasGeneric && hasEntries(this.privacyTexts)) {
          patch.content.texts = { ...this.privacyTexts }
        }
      }

      if (this.mediaKeys && Object.keys(this.mediaKeys).length) {
        patch.content.media_keys = { ...this.mediaKeys }
      }

      if (!Object.keys(patch.content).length) return null
      patch.is_published = true
      return { pageKey: key, payload: patch }
    },
    async savePrivacyTexts(){
      try {
        const built = this.buildLegacyPageContentPayload()
        if (!built) {
          alert('保存対象のデータがありません')
          return
        }
        const res = await apiClient.adminUpdatePageContent(built.pageKey, built.payload)
        if (res && res.success) {
          alert('保存しました')
        } else {
          const msg = (res && (res.error || res.message)) || '保存に失敗しました（認証切れの可能性あり）'
          alert(msg)
        }
      } catch(_) { alert('保存に失敗しました') }
    },
    openCreate(){ this.showCreate = true },
    async create(){
      if (!this.createForm.slug || !this.createForm.title) return
      const res = await apiClient.createCmsPage({ ...this.createForm })
      if (res.success){ this.showCreate=false; this.createForm={slug:'', title:''}; await this.loadPages(); this.selectPage(res.data) }
    }
  }
}
</script>

  <style scoped>
  .cms{ display:flex; gap:0; min-height: calc(100vh - 140px); background:#fff; border-radius:8px; overflow:hidden; }
  .pane{ border-right:1px solid #eee; }
  .left{ width:280px; }
  .center{ flex:1; padding:16px; }
  .right{ width:360px; }
.toolbar{ display:flex; gap:8px; padding:10px; border-bottom:1px solid #eee; }
.list{ overflow:auto; height: calc(100% - 50px); }
.item{ padding:10px 12px; border-bottom:1px solid #f4f4f4; cursor:pointer; }
.item.active{ background:#fff2f4; }
.title{ font-weight:600; }
.slug{ color:#777; font-size:12px; }
.editor-form{ max-width: 860px; margin: 0 auto; }
.field{ margin-bottom:12px; display:flex; flex-direction:column; gap:6px; }
.input, .textarea{ border:1px solid #ddd; border-radius:6px; padding:8px 10px; }
.btn{ background:#1A1A1A; color:#fff; border:none; border-radius:6px; padding:8px 12px; cursor:pointer; }
.btn.primary{ background:#DA5761; }
.btn.danger{ background:#e25555; }
.empty{ padding:16px; color:#777; }
.modal{ position:fixed; inset:0; background:rgba(0,0,0,0.4); display:flex; align-items:center; justify-content:center; }
.modal-inner{ background:#fff; border-radius:8px; padding:16px; width:360px; display:flex; flex-direction:column; gap:10px; }
.actions{ display:flex; justify-content:flex-end; }
  .actions-row{ display:flex; gap:8px; justify-content:center; padding-top:8px; }
  .section-title{ background:#e6f0ff; color:#1a3a7c; padding:6px 10px; border-left:4px solid #2d5bd1; margin:10px 0; font-weight:600; }
  .help{ color:#777; font-size:12px; }
  /* Preview panel removed */
  .page-image-row{ display:flex; gap:12px; align-items:center; border:1px solid #eee; border-radius:8px; padding:10px; margin-bottom:8px; background:#fafafa; }
  .img-preview{ width:120px; height:80px; background:#fff; border:1px solid #eee; display:flex; align-items:center; justify-content:center; }
  .img-preview img{ max-width:100%; max-height:100%; object-fit:contain; }
  .img-meta{ display:flex; flex-direction:column; gap:6px; }
  .img-key{ font-weight:600; }
  .img-file{ color:#777; font-size:12px; }
  /* Layout oriented editor */
  .layout-grid{ display:grid; grid-template-columns: 1fr 280px; gap:16px; align-items:start; }
  .text-col{ display:flex; flex-direction:column; gap:8px; }
  .image-col{ display:flex; flex-direction:column; gap:8px; }
  .field-group{ border:1px solid #eee; border-radius:8px; padding:10px; background:#fff; }
  .group-title{ font-weight:600; color:#1A1A1A; margin-bottom:6px; }
  .staff-card{ border:1px solid #eee; border-radius:10px; padding:12px; background:#fff; margin-bottom:12px; }
  .staff-card-header{ display:flex; justify-content:space-between; align-items:center; gap:8px; }
  .staff-card-title{ font-weight:600; color:#1A1A1A; }
  .staff-card-actions{ display:flex; gap:6px; flex-wrap:wrap; }
  @media (max-width: 960px){
    .layout-grid{ grid-template-columns: 1fr; }
  }
  </style>
