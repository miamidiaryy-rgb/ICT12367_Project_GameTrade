<script setup>
import { ref, computed, onMounted } from 'vue'
import { API_BASE_URL } from '../config'

const blacklist = ref([])
const searchText = ref('')

const loadBlacklist = async () => {
  const res = await fetch(`${API_BASE_URL}/reports_api.php`)
  const json = await res.json()
  if (json.success) blacklist.value = json.blacklist
}

const filtered = computed(() => {
  const kw = searchText.value.trim().toLowerCase()
  if (!kw) return blacklist.value
  return blacklist.value.filter(
    (b) =>
      String(b.seller_id).includes(kw) ||
      b.seller_username.toLowerCase().includes(kw) ||
      b.seller_name.toLowerCase().includes(kw)
  )
})

onMounted(loadBlacklist)
</script>

<template>
  <div class="container mt-4 mb-5">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
      <div>
        <h4 class="mb-0 text-danger">ตรวจสอบบัญชีดำผู้ขาย (Blacklist Seller)</h4>
        <div class="small text-muted">ตรวจสอบประวัติผู้ขายที่ผ่านการพิจารณาจากผู้ดูแลระบบก่อนตัดสินใจซื้อขาย</div>
      </div>
      <input
        v-model="searchText"
        type="text"
        class="form-control form-control-sm"
        placeholder="ค้นหา User ID หรือชื่อผู้ขาย..."
        style="width: 240px"
      />
    </div>

    <div class="table-responsive">
      <table class="table table-bordered table-striped align-middle">
        <thead class="table-danger">
          <tr>
            <th class="text-center" style="width: 90px">User ID</th>
            <th>ชื่อผู้ใช้</th>
            <th>ชื่อผู้ขาย</th>
            <th>เหตุผลที่ถูกขึ้นบัญชีดำ</th>
            <th style="width: 155px">วันที่ขึ้นบัญชีดำ</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="b in filtered" :key="b.blacklist_id">
            <td class="text-center fw-semibold">{{ b.seller_id }}</td>
            <td>@{{ b.seller_username }}</td>
            <td class="text-danger fw-semibold">{{ b.seller_name }}</td>
            <td>{{ b.reason }}</td>
            <td>{{ b.added_at }}</td>
          </tr>
          <tr v-if="filtered.length === 0">
            <td colspan="5" class="text-center text-muted py-3">ไม่พบรายชื่อผู้ขายในบัญชีดำ</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
