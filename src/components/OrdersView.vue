<script setup>
import { ref, onMounted } from 'vue'
import { API_BASE_URL } from '../config'

const props = defineProps({
  user: { type: Object, required: true },
})

const orders = ref([])
const reviews = ref([])
const statusMsg = ref('')
const reviewTarget = ref(null)
const reviewForm = ref({ rating: 5, comment: '' })

const loadData = async () => {
  const res = await fetch(`${API_BASE_URL}/orders_api.php`)
  const json = await res.json()
  if (json.success) {
    orders.value = json.orders
    reviews.value = json.reviews
  }
}

const submitReview = async () => {
  const res = await fetch(`${API_BASE_URL}/orders_api.php`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      action: 'add_review',
      order_id: reviewTarget.value.order_id,
      buyer_id: props.user.user_id,
      seller_id: reviewTarget.value.seller_id,
      rating: reviewForm.value.rating,
      comment: reviewForm.value.comment,
    }),
  })
  const json = await res.json()
  if (json.success) {
    statusMsg.value = json.message
    reviewTarget.value = null
    reviewForm.value = { rating: 5, comment: '' }
    await loadData()
  }
}

onMounted(loadData)
</script>

<template>
  <div class="container mt-4 mb-5">
    <h4 class="mb-3">คำสั่งซื้อและประวัติการซื้อขาย (Trading History)</h4>
    <div v-if="statusMsg" class="alert alert-success py-2">{{ statusMsg }}</div>

    <div class="table-responsive mb-4">
      <table class="table table-bordered table-striped align-middle">
        <thead class="table-dark">
          <tr>
            <th class="text-center" style="width: 75px">รหัส</th>
            <th>รายการไอเทม</th>
            <th>ผู้ซื้อ</th>
            <th>ผู้ขาย</th>
            <th class="text-end">ราคา (บาท)</th>
            <th class="text-center">สถานะ</th>
            <th class="text-center" style="width: 120px">รีวิวผู้ขาย</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="ord in orders" :key="ord.order_id">
            <td class="text-center">#{{ ord.order_id }}</td>
            <td>{{ ord.item_name }}</td>
            <td>{{ ord.buyer_name }}</td>
            <td>{{ ord.seller_name }}</td>
            <td class="text-end">{{ Number(ord.price).toLocaleString('en-US', { minimumFractionDigits: 2 }) }}</td>
            <td class="text-center"><span class="badge bg-success">{{ ord.order_status }}</span></td>
            <td class="text-center">
              <button class="btn btn-outline-primary btn-sm" @click="reviewTarget = ord">ให้คะแนน</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <h5 class="mb-2">รีวิวและคะแนนผู้ขายล่าสุด</h5>
    <div class="row g-2">
      <div v-for="rev in reviews" :key="rev.review_id" class="col-md-6">
        <div class="border rounded p-3 bg-light small">
          <div class="d-flex justify-content-between mb-1">
            <strong>ผู้ขาย: {{ rev.seller_name }}</strong>
            <span class="badge bg-warning text-dark">★ {{ rev.rating }} / 5</span>
          </div>
          <div class="mb-1">{{ rev.comment }}</div>
          <div class="text-muted">โดย {{ rev.buyer_name }} (คำสั่งซื้อ #{{ rev.order_id }})</div>
        </div>
      </div>
    </div>

    <div v-if="reviewTarget" class="modal d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.45)">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">ให้คะแนนและรีวิวผู้ขาย ({{ reviewTarget.seller_name }})</h5>
            <button type="button" class="btn-close" @click="reviewTarget = null"></button>
          </div>
          <form @submit.prevent="submitReview">
            <div class="modal-body">
              <div class="mb-3">
                <label class="form-label">คะแนน (1–5 ดาว)</label>
                <select v-model="reviewForm.rating" class="form-select">
                  <option :value="5">5 ดาว — ดีมาก</option>
                  <option :value="4">4 ดาว — ดี</option>
                  <option :value="3">3 ดาว — ปานกลาง</option>
                  <option :value="2">2 ดาว — พอใช้</option>
                  <option :value="1">1 ดาว — ควรปรับปรุง</option>
                </select>
              </div>
              <div class="mb-2">
                <label class="form-label">ความคิดเห็น</label>
                <textarea v-model="reviewForm.comment" rows="3" class="form-control" required></textarea>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary btn-sm" @click="reviewTarget = null">ยกเลิก</button>
              <button type="submit" class="btn btn-primary btn-sm">บันทึกรีวิว</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>
