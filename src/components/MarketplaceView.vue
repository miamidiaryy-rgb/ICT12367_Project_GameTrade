<script setup>
import { ref, computed, onMounted } from 'vue'
import { API_BASE_URL } from '../config'

const props = defineProps({
  user: { type: Object, default: null },
})

const items = ref([])
const games = ref([])
const categories = ref([])
const reviews = ref([])

const searchText = ref('')
const selectedGame = ref('')
const selectedCategory = ref('')

const detailItem = ref(null)
const showReportModal = ref(false)
const statusMsg = ref('')
const errorMsg = ref('')

const reportForm = ref({
  reason_type: 'โกงเงิน / ไม่ส่งไอเทม',
  detail: '',
  evidence_file: '',
})

const imgUrl = (fn) => (fn ? `${API_BASE_URL}/uploads/${fn}` : '')

const loadData = async () => {
  const [itemRes, ordRes] = await Promise.all([
    fetch(`${API_BASE_URL}/items_api.php`),
    fetch(`${API_BASE_URL}/orders_api.php`),
  ])
  const itemJson = await itemRes.json()
  const ordJson = await ordRes.json()
  if (itemJson.success) {
    items.value = itemJson.items
    games.value = itemJson.games
    categories.value = itemJson.categories
  }
  if (ordJson.success) reviews.value = ordJson.reviews
}

const filteredItems = computed(() => {
  const kw = searchText.value.trim().toLowerCase()
  return items.value.filter((i) => {
    const mGame = !selectedGame.value || Number(i.game_id) === Number(selectedGame.value)
    const mCat = !selectedCategory.value || Number(i.category_id) === Number(selectedCategory.value)
    const mText = !kw || i.item_name.toLowerCase().includes(kw) || i.seller_name.toLowerCase().includes(kw)
    return mGame && mCat && mText
  })
})

const sellerReviews = computed(() => {
  if (!detailItem.value) return []
  return reviews.value.filter((r) => Number(r.seller_id) === Number(detailItem.value.seller_id))
})

const buyItem = async (item) => {
  if (!props.user) {
    errorMsg.value = 'กรุณาเข้าสู่ระบบก่อนทำรายการสั่งซื้อ'
    return
  }
  statusMsg.value = ''
  errorMsg.value = ''
  const res = await fetch(`${API_BASE_URL}/orders_api.php`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      action: 'create_order',
      item_id: item.item_id,
      buyer_id: props.user.user_id,
      seller_id: item.seller_id,
      price: item.price,
    }),
  })
  const json = await res.json()
  if (json.success) {
    statusMsg.value = `สั่งซื้อ "${item.item_name}" เรียบร้อยแล้ว (รหัสคำสั่งซื้อ #${json.order_id})`
    detailItem.value = null
    await loadData()
  } else {
    errorMsg.value = json.message || 'สั่งซื้อไม่สำเร็จ'
  }
}

const openReport = () => {
  if (!props.user) {
    errorMsg.value = 'กรุณาเข้าสู่ระบบก่อนแจ้งรายงานผู้ขาย'
    return
  }
  reportForm.value = { reason_type: 'โกงเงิน / ไม่ส่งไอเทม', detail: '', evidence_file: '' }
  showReportModal.value = true
}

const submitReport = async () => {
  const res = await fetch(`${API_BASE_URL}/reports_api.php`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      action: 'create_report',
      reporter_id: props.user.user_id,
      seller_id: detailItem.value.seller_id,
      item_id: detailItem.value.item_id,
      ...reportForm.value,
    }),
  })
  const json = await res.json()
  if (json.success) {
    statusMsg.value = json.message
    showReportModal.value = false
    detailItem.value = null
  }
}

onMounted(loadData)
</script>

<template>
  <div class="container mt-4 mb-5">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
      <h4 class="mb-0">ตลาดซื้อขายไอเทมเกม (Marketplace)</h4>
      <div class="d-flex flex-wrap gap-2">
        <select v-model="selectedGame" class="form-select form-select-sm" style="width: 150px">
          <option value="">ทุกเกม</option>
          <option v-for="g in games" :key="g.game_id" :value="g.game_id">{{ g.game_name }}</option>
        </select>
        <select v-model="selectedCategory" class="form-select form-select-sm" style="width: 150px">
          <option value="">ทุกประเภท</option>
          <option v-for="c in categories" :key="c.category_id" :value="c.category_id">{{ c.category_name }}</option>
        </select>
        <input
          v-model="searchText"
          type="text"
          class="form-control form-control-sm"
          placeholder="ค้นหาชื่อไอเทมหรือผู้ขาย..."
          style="width: 210px"
        />
      </div>
    </div>

    <div v-if="statusMsg" class="alert alert-success py-2">{{ statusMsg }}</div>
    <div v-if="errorMsg" class="alert alert-danger py-2">{{ errorMsg }}</div>

    <div class="row row-cols-1 row-cols-md-3 g-3">
      <div v-for="item in filteredItems" :key="item.item_id" class="col">
        <div
          class="card h-100"
          :class="{ 'border-danger': Number(item.is_blacklisted) === 1 }"
        >
          <img
            :src="imgUrl(item.image)"
            :alt="item.item_name"
            class="card-img-top"
            style="height: 145px; object-fit: cover"
          />
          <div class="card-body d-flex flex-column">
            <div class="d-flex justify-content-between align-items-start mb-1">
              <span class="badge bg-secondary">{{ item.game_name }}</span>
              <span
                class="badge"
                :class="item.status === 'พร้อมขาย' ? 'bg-success' : 'bg-dark'"
              >
                {{ item.status }}
              </span>
            </div>

            <h6 class="card-title fw-semibold mt-1 mb-1">{{ item.item_name }}</h6>
            <div class="small text-muted mb-2">
              ประเภท: {{ item.category_name }} | ผู้ขาย: {{ item.seller_name }}
              <span v-if="Number(item.avg_rating) > 0" class="text-warning-emphasis ms-1">
                ★ {{ item.avg_rating }}
              </span>
            </div>

            <div
              v-if="Number(item.is_blacklisted) === 1"
              class="alert alert-danger py-1 px-2 small mb-2"
            >
              <strong>คำเตือน Blacklist:</strong> ผู้ขายนี้อยู่ในบัญชีดำ โปรดระมัดระวังก่อนซื้อขาย
            </div>

            <p class="card-text small text-secondary flex-grow-1 mb-2">{{ item.description }}</p>

            <div class="d-flex justify-content-between align-items-center pt-2 border-top">
              <strong class="text-primary">{{ Number(item.price).toLocaleString('en-US', { minimumFractionDigits: 2 }) }} บาท</strong>
              <button class="btn btn-outline-primary btn-sm" @click="detailItem = item">
                ดูรายละเอียด
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal รายละเอียดสินค้า + คำเตือน Blacklist + รีวิวผู้ขาย -->
    <div v-if="detailItem" class="modal d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.45)">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">{{ detailItem.item_name }}</h5>
            <button type="button" class="btn-close" @click="detailItem = null"></button>
          </div>
          <div class="modal-body">
            <div v-if="Number(detailItem.is_blacklisted) === 1" class="alert alert-danger">
              <strong>คำเตือน: ผู้ขายอยู่ในบัญชีดำ (Blacklist Seller)!</strong><br />
              เหตุผล: {{ detailItem.blacklist_reason }}
            </div>

            <div class="row g-3">
              <div class="col-md-5">
                <img :src="imgUrl(detailItem.image)" class="img-fluid rounded border w-100" />
              </div>
              <div class="col-md-7">
                <p class="mb-1"><strong>เกม:</strong> {{ detailItem.game_name }} | <strong>ประเภท:</strong> {{ detailItem.category_name }}</p>
                <p class="mb-1"><strong>ผู้ขาย:</strong> {{ detailItem.seller_name }} (User ID: {{ detailItem.seller_id }} / @{{ detailItem.seller_username }})</p>
                <p class="mb-2"><strong>คะแนนเฉลี่ยผู้ขาย:</strong> {{ Number(detailItem.avg_rating) > 0 ? `★ ${detailItem.avg_rating} / 5 (${detailItem.review_count} รีวิว)` : 'ยังไม่มีรีวิว' }}</p>
                <p class="mb-2"><strong>รายละเอียด:</strong> {{ detailItem.description }}</p>
                <h5 class="text-primary mb-3">ราคา {{ Number(detailItem.price).toLocaleString('en-US', { minimumFractionDigits: 2 }) }} บาท</h5>

                <div class="d-flex gap-2">
                  <button
                    class="btn btn-primary btn-sm"
                    :disabled="detailItem.status !== 'พร้อมขาย'"
                    @click="buyItem(detailItem)"
                  >
                    {{ detailItem.status === 'พร้อมขาย' ? 'สั่งซื้อไอเทมนี้' : 'ขายแล้ว' }}
                  </button>
                  <button class="btn btn-outline-danger btn-sm" @click="openReport">
                    แจ้งรายงานผู้ขาย
                  </button>
                </div>
              </div>
            </div>

            <hr />
            <h6 class="fw-semibold">รีวิวจากผู้ซื้อถึงผู้ขายรายนี้</h6>
            <div v-if="sellerReviews.length === 0" class="text-muted small">ยังไม่มีรีวิวสำหรับผู้ขายรายนี้</div>
            <div v-for="rev in sellerReviews" :key="rev.review_id" class="border rounded p-2 mb-2 bg-light small">
              <div class="d-flex justify-content-between">
                <strong>{{ rev.buyer_name }}</strong>
                <span class="text-warning-emphasis">★ {{ rev.rating }} / 5</span>
              </div>
              <div>{{ rev.comment }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal แจ้งรายงานผู้ขาย -->
    <div v-if="showReportModal" class="modal d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5)">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header bg-danger text-white">
            <h5 class="modal-title">แจ้งรายงานผู้ขาย ({{ detailItem?.seller_name }})</h5>
            <button type="button" class="btn-close btn-close-white" @click="showReportModal = false"></button>
          </div>
          <form @submit.prevent="submitReport">
            <div class="modal-body">
              <div class="mb-3">
                <label class="form-label">เหตุผลการรายงาน</label>
                <select v-model="reportForm.reason_type" class="form-select" required>
                  <option value="โกงเงิน / ไม่ส่งไอเทม">โกงเงิน / ไม่ส่งไอเทม</option>
                  <option value="ข้อมูลสินค้าไม่ตรงกับประกาศ">ข้อมูลสินค้าไม่ตรงกับประกาศ</option>
                  <option value="พฤติกรรมน่าสงสัย / แอบอ้าง">พฤติกรรมน่าสงสัย / แอบอ้าง</option>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label">รายละเอียดเหตุการณ์</label>
                <textarea v-model="reportForm.detail" rows="3" class="form-control" required></textarea>
              </div>
              <div class="mb-2">
                <label class="form-label">แนบหลักฐาน (ชื่อไฟล์ภาพ/ลิงก์หลักฐาน)</label>
                <input v-model="reportForm.evidence_file" type="text" class="form-control" placeholder="เช่น slip_evidence.jpg" required />
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary btn-sm" @click="showReportModal = false">ยกเลิก</button>
              <button type="submit" class="btn btn-danger btn-sm">ส่งรายงานให้ผู้ดูแลตรวจสอบ</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>
