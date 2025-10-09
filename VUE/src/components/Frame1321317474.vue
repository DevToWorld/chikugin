<template>
  <div :class="[`frame-1321317474`, className || ``]" @click="handleClick" style="cursor: pointer;">
    <div class="frame-1321317473-2">
      <div class="overlap-group1" :style="bgStyle">
        <div class="overlap-group-6">
          <div class="date-2 valign-text-middle inter-normal-ship-gray-15px">{{ date || '2025.04.28' }}</div>
          <div
            class="text-8 valign-text-middle inter-semi-bold-ship-gray-16px"
            v-shrink-on-wrap="{ lines: 2, className: 'shrink-2lines' }"
          >
            {{ title || '事業継承から描く九州の未来' }}
          </div>
          <div class="viwe-more-2 valign-text-middle inter-normal-ship-gray-10px">View More .</div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import shrinkOnWrap from '@/directives/shrinkOnWrap'
import { getApiBaseUrl } from '@/config/api'
export default {
  name: "Frame1321317474",
  props: ["x22", "className", "date", "title", "reportId", "reportType"],
  directives: { shrinkOnWrap },
  methods: {
    handleClick() {
      // reportTypeが'economic_report'の場合は経済統計ページへ、それ以外は刊行物ページへ
      if (this.reportType === 'economic_report' && this.reportId) {
        this.$router.push(`/economic-statistics/${this.reportId}/detail`);
      } else {
        this.$router.push('/publication');
      }
    }
  },
  computed: {
    bgStyle() {
      let url = this.x22 || '/img/image-1.png'
      
      // Convert relative URLs to full URLs with API host
      if (!url.startsWith('http://') && !url.startsWith('https://')) {
        if (url.startsWith('/')) {
          url = getApiBaseUrl() + url
        } else {
          url = getApiBaseUrl() + '/' + url
        }
      }
      
      return {
        backgroundImage: `url(${url})`,
        backgroundSize: 'cover',
        backgroundPosition: 'center',
        backgroundRepeat: 'no-repeat'
      }
    }
  }
};
</script>

<style>
.frame-1321317474 {
  height: 398px;
  position: relative;
  width: 583px;
}

.frame-1321317473-2 {
  align-items: flex-end;
  border-radius: 10px;
  display: flex;
  height: 398px;
  overflow: hidden;
  width: 583px;
}

.overlap-group1 {
  height: 397px;
  position: relative;
  width: 583px;
}


.overlap-group-6 {
  align-items: flex-start;
  background-color: var(--white);
  border-radius: 15px 0px 0px 0px;
  display: flex;
  flex-direction: column;
  left: 76px;
  min-height: 115px;
  padding: 13px 29.1px;
  position: absolute;
  top: 282px;
  width: 507px;
}

.date-2 {
  height: 23px;
  letter-spacing: 0;
  line-height: 22.5px;
  margin-top: 3px;
  width: 141px;
}

.text-8 {
  height: 24px;
  letter-spacing: 0;
  line-height: 18px;
  margin-top: 6px;
  width: 100%;
  word-wrap: break-word;
  overflow-wrap: break-word;
  font-size: 12px;
}

/* When the title wraps to 2+ lines, gently shrink */
.text-8.shrink-2lines {
  height: auto;            /* allow 2 lines without overlap */
  line-height: 1.2em;      /* tighter line spacing on wrap */
  font-size: 12px;         /* keep at ~12px when wrapping */
}

.viwe-more-2 {
  height: 15px;
  letter-spacing: 0;
  line-height: 15px;
  margin-top: 18px;
  text-decoration: underline;
  width: 97px;
}

.frame-1321317474.frame-1321317472-1 {
  align-items: flex-start;
  display: flex;
  left: 0;
  position: absolute;
  top: 0;
}

.frame-1321317474.frame-1321317472-1 .frame-1321317473-2 {
  border-radius: unset;
  overflow: unset;
}

.frame-1321317474.frame-1321317472-1 {
  cursor: pointer;
  transition: transform 0.3s ease, opacity 0.3s ease;
}

.frame-1321317474.frame-1321317472-1:hover {
  transform: scale(1.02);
  opacity: 0.9;
}

/* レスポンシブスタイル */
@media (max-width: 1100px) {
  .text-8 {
    font-size: 12px !important;
    line-height: 18px !important;
  }
}

@media (max-width: 768px) {
  .frame-1321317474 {
    width: 100%;
    max-width: 583px;
  }
  
  .frame-1321317473-2 {
    width: 100%;
    max-width: 583px;
  }
  
  .overlap-group1 {
    width: 100%;
    max-width: 583px;
  }
  
  
  .overlap-group-6 {
    width: 75%;
    max-width: 507px;
    right: 0px;
    left: auto;
    padding: 15px 20px;
    border-radius: 15px 0px 0px 0px;
  }
  
  .text-8 {
    font-size: 12px !important;
    line-height: 18px !important;
  }
}

/* 1550px以上で横幅を900pxに変更 */
@media (min-width: 1550px) {
  .frame-1321317474 {
    width: 900px !important;
    height: 500px !important;
  }
  
  .frame-1321317473-2 {
    width: 900px !important;
    height: 500px !important;
  }
  
  .overlap-group1 {
    width: 900px !important;
    height: 499px !important;
  }
  
  
  .overlap-group-6 {
    width: 75% !important;
    max-width: 676px !important;
    right: 0px !important;
    left: auto !important;
    top: 384px !important;
    padding: 15px 20px !important;
    border-radius: 15px 0px 0px 0px !important;
  }
}

@media (max-width: 480px) {
  .frame-1321317474 {
    height: auto !important;
  }
  
  .frame-1321317474.frame-1321317472-1 {
    height: auto !important;
  }
  
  .frame-1321317474.frame-1321317472-1:hover {
    transform: none;
    opacity: 1;
  }
  
  .frame-1321317473-2 {
    height: auto !important;
  }
  
  .overlap-group1 {
    height: 300px !important;
  }
  
  
  .overlap-group-6 {
    top: 200px !important;
    min-height: 0px !important;
    width: 100% !important;
    max-width: 100% !important;
    border-radius: 15px 15px 0px 0px !important;
  }
  
  .text-8 {
    font-size: 12px !important;
    line-height: 18px !important;
  }
}
</style>
