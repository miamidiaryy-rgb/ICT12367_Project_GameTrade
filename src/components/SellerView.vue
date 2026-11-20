<script setup>
import { ref, computed, onMounted } from 'vue'
import { API_BASE_URL } from '../config'

const props = defineProps({
  user: { type: Object, required: true },
})

const items = ref([])
const games = ref([])
const categories = ref([])
const statusMsg = ref('')
const showForm = ref(false)
const isEdit = ref(false)

const form = ref({
  item_id: null,
  game_id: 1,
  category_id: 1,
  item_name: '',
  description: '',
  price: '',
  image: 'valorant_skin.jpg',
  status: 'พร้อมขาย',
})

const loadData = async () => {
  const res = await fetch(`${API_BASE_URL}/items_api.php`)
  const json = await res.json()
  if (json.success) {
    items.value = json.items
    games.value = json.games
    categories.value = json.categories
  }
}

const myItems = computed(() =>
  items.value.filter((i) => Number(i.seller_id) === Number(props.user.user_id) || props.user.role === 'admin')
)

const openAdd = () => {
  isEdit.value = false
  form.value = { item_id: null, game_id: 1, category_id: 1, item_name: '', description: '', price: '', image: 'valorant_skin.jpg', status: 'พร้อมขาย' }
  showForm.value = true
}

const openEdit = (item) => {
  isEdit.value = true
  form.value = { ...item }
  showForm.value = true
}

const saveItem = async () => {
  const res = await fetch(`${API_BASE_URL}/items_api.php`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      action: isEdit.value ? 'update' : 'create',
      seller_id: props.user.user_id,
      ...form.value,
    }),
  })
  const json = await res.json()
  if (json.success) {
    statusMsg.value = json.message
    showForm.value = false
    await loadData()
  }
}

const deleteItem = async (itemId) => {
  const res = await fetch(`${API_BASE_URL}/items_api.php`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action: 'delete', item_id: itemId }),
  })
  const json = await res.json()
  if (json.success) {
    statusMsg.value = json.message
    await loadData()
  }
}

onMounted(loadData)
</script>

<template>
  <div class="container mt-4 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h4 class="mb-0">จัดการประกาศขายไอเทม (Seller Management)</h4>
      <button v-if="!showForm" class="btn btn-primary btn-sm" @click="openAdd">+ ลงประกาศขายไอเทม</button>
      <button v-else class="btn btn-secondary btn-sm" @click="showForm = false">กลับไปรายการประกาศ</button>
    </div>

    <div v-if="statusMsg" class="alert alert-success py-2">{{ statusMsg }}</div>

    <div v-if="showForm" class="card mx-auto mb-4" style="max-width: 660px">
      <div class="card-header bg-light fw-semibold">
        {{ isEdit ? 'แก้ไขประกาศขายไอเทม' : 'ลงประกาศขายไอเทมใหม่' }}
      </div>
      <div class="card-body">
        <form @submit.prevent="saveItem">
          <div class="mb-3">
            <label class="form-label">ชื่อไอเทม</label>
            <input v-model="form.item_name" type="text" class="form-control" required />
          </div>
          <div class="row mb-3">
            <div class="col-md-6">
              <label class="form-label">เกม</label>
              <select v-model="form.game_id" class="form-select">
                <option v-for="g in games" :key="g.game_id" :value="g.game_id">{{ g.game_name }}</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">ประเภทไอเทม</label>
              <select v-model="form.category_id" class="form-select">
                <option v-for="c in categories" :key="c.category_id" :value="c.category_id">{{ c.category_name }}</option>
              </select>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">รายละเอียด</label>
            <textarea v-model="form.description" rows="3" class="form-control" required></textarea>
          </div>
          <div class="row mb-3">
            <div class="col-md-4">
              <label class="form-label">ราคา (บาท)</label>
              <input v-model="form.price" type="number" step="0.01" min="0" class="form-control" required />
            </div>
            <div class="col-md-4">
              <label class="form-label">รูปภาพ</label>
              <input v-model="form.image" type="text" class="form-control" />
            </div>
            <div class="col-md-4">
              <label class="form-label">สถานะสินค้า</label>
              <select v-model="form.status" class="form-select">
                <option value="พร้อมขาย">พร้อมขาย</option>
                <option value="ขายแล้ว">ขายแล้ว</option>
              </select>
            </div>
          </div>
          <div class="text-end">
            <button type="submit" class="btn btn-primary btn-sm">บันทึกประกาศขาย</button>
          </div>
        </form>
      </div>
    </div>

    <div v-else class="table-responsive">
      <table class="table table-bordered table-striped align-middle">
        <thead class="table-dark">
          <tr>
            <th class="text-center" style="width: 60px">#</th>
            <th>ชื่อไอเทม</th>
            <th>เกม</th>
            <th>ประเภท</th>
            <th class="text-end">ราคา (บาท)</th>
            <th class="text-center">สถานะ</th>
            <th class="text-center" style="width: 140px">จัดการ</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in myItems" :key="item.item_id">
            <td class="text-center">{{ item.item_id }}</td>
            <td>{{ item.item_name }}</td>
            <td>{{ item.game_name }}</td>
            <td>{{ item.category_name }}</td>
            <td class="text-end">{{ Number(item.price).toLocaleString('en-US', { minimumFractionDigits: 2 }) }}</td>
            <td class="text-center">
              <span class="badge" :class="item.status === 'พร้อมขาย' ? 'bg-success' : 'bg-secondary'">
                {{ item.status }}
              </span>
            </td>
            <td class="text-center">
              <button class="btn btn-warning btn-sm me-1" @click="openEdit(item)">แก้ไข</button>
              <button class="btn btn-danger btn-sm" @click="deleteItem(item.item_id)">ลบ</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
