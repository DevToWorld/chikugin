<template>
  <div class="breadcrumbs" v-if="breadcrumbs && breadcrumbs.length > 0">
    <router-link to="/" class="breadcrumb-item">トップ</router-link>
    <template v-for="(item, index) in breadcrumbs">
      <span :key="`separator-${index}`" class="breadcrumb-separator">></span>
      <router-link 
        v-if="index !== breadcrumbs.length - 1"
        :key="`item-${index}`"
        :to="getBreadcrumbLink(item, index)"
        class="breadcrumb-item"
      >
        {{ item.text || item }}
      </router-link>
      <span 
        v-else
        :key="`item-${index}`"
        class="breadcrumb-item current"
      >
        {{ item.text || item }}
      </span>
    </template>
  </div>
</template>

<script>
export default {
  name: "Breadcrumbs",
  props: {
    breadcrumbs: {
      type: Array,
      default: () => []
    }
  },
  methods: {
    getBreadcrumbLink(item, index) {
      // オブジェクト形式でリンクが指定されている場合
      if (typeof item === 'object' && item.link) {
        return item.link;
      }
      
      // 文字列の場合、パンくずリストのテキストから適切なリンクを推定
      const text = item.text || item;
      const linkMap = {
        'セミナー': '/seminars',
        '現在のセミナー': '/seminars/current',
        '過去のセミナー': '/seminar/archive',
        'セミナー詳細': '/seminars',
        'セミナー詳細（予約受付中）': '/seminars',
        'セミナー詳細（予約済み）': '/seminars',
        'セミナー詳細（参加済み）': '/seminars',
        'セミナー申し込み': '/seminars',
        'お知らせ': '/news',
        'ニュース': '/news',
        '刊行物': '/publications',
        '会社概要': '/company',
        '私たちについて': '/aboutus',
        'よくあるご質問': '/faq',
        'FAQ': '/faq',
        '入会案内': '/membership',
        '会員登録': '/membership',
        'スタンダード会員': '/membership/standard',
        'プレミアム会員': '/membership/premium',
        '各種情報': '/economic-indicators',
        '経済指標一覧': '/economic-indicators',
        '経済・調査統計': '/economic-statistics',
        'サイトマップ': '/sitemap',
        'プライバシーポリシー': '/privacy',
        '利用規約': '/terms',
        '特定商取引法': '/legal',
        'お問い合わせ': '/contact'
      };
      
      return linkMap[text] || '/';
    }
  }
};
</script>

<style scoped>
/* Breadcrumbs */
.breadcrumbs {
  padding: 24px 46px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.breadcrumb-item {
  color: #3F3F3F;
  font-size: 12px;
  font-weight: 300;
  line-height: 5;
  text-decoration: none;
  transition: color 0.3s ease;
}

.breadcrumb-item:hover {
  color: #DA5761;
  text-decoration: underline;
}

.breadcrumb-item.current {
  color: #1A1A1A;
  font-weight: 500;
  cursor: default;
}

.breadcrumb-item.current:hover {
  color: #1A1A1A;
  text-decoration: none;
}

.breadcrumb-separator {
  color: #3F3F3F;
  font-size: 12px;
  font-weight: 300;
  line-height: 5;
}

/* レスポンシブ対応 */
@media (max-width: 1150px) {
  .breadcrumbs {
    padding: 22px 35px;
    gap: 6px;
  }
  
  .breadcrumb-item,
  .breadcrumb-separator {
    font-size: 11px;
  }
}

@media (max-width: 800px) {
  .breadcrumbs {
    padding: 20px 25px;
    gap: 5px;
  }
  
  .breadcrumb-item,
  .breadcrumb-separator {
    font-size: 10px;
    line-height: 4;
  }
}

@media (max-width: 600px) {
  .breadcrumbs {
    padding: 18px 20px;
    gap: 4px;
  }
  
  .breadcrumb-item,
  .breadcrumb-separator {
    font-size: 9px;
    line-height: 3.5;
  }
}

@media (max-width: 480px) {
  .breadcrumbs {
    padding: 15px 15px;
    gap: 3px;
  }
  
  .breadcrumb-item,
  .breadcrumb-separator {
    font-size: 8px;
    line-height: 3;
  }
}
</style>
