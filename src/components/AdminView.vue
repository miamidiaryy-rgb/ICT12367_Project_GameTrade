<script setup>
import { ref, onMounted } from 'vue'
import { API_BASE_URL } from '../config'

const props = defineProps({
  user: { type: Object, required: true },
})

const tab = ref('reports') // 'reports' | 'blacklist' | 'users'
const reports = ref([])
const blacklist = ref([])
const users = ref([])
const statusMsg = ref('')

const loadData = async () => {
  const res = await fetch(`${API_BASE_URL}/reports_api.php`)
  const json = await res.json()
  if (json.success) {
    reports.value = json.reports
    blacklist.value = json.blacklist
    users.value = json.users
  }
}

const reviewReport = async (rep, decision) => {
  statusMsg.value = ''
  const res = await fetch(`${API_BASE_URL}/reports_api.php`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      action: 'review_report',
      report_id: rep.report_id,
      seller_id: rep.seller_id,
      status: decision,
      reason: `${rep.reason_type}: ${rep.detail}`,
      admin_id: props.user.user_id,
    }),
  })
  const json = await res.json()
  if (json.success) {
    statusMsg.value =
      decision === 'อนุมัติ'
        ? `อนุมัติรายงาน #${rep.report_id} และเพิ่ม ${rep.seller_name} ลงใน Blacklist แล้ว`
        : `ปฏิเสธรายงาน #${rep.report_id} เรียบร้อยแล้ว`
    await loadData()
  }
}

const removeFromBlacklist = async (sellerId, sellerName) => {
  statusMsg.value = ''
  const res = await fetch(`${API_BASE_URL}/reports_api.php`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action: 'remove_blacklist', seller_id: sellerId }),
  })
  const json = await res.json()
  if (json.success) {
    statusMsg.value = `ปลด ${sellerName} ออกจาก Blacklist เรียบร้อยแล้ว`
    await loadData()
  }
}

onMounted(loadData)
</script>

<template>
  <div class="container mt-4 mb-5">
    <h4 class="mb-3">ระบบผู้ดูแลเว็บไซต์ (Admin Dashboard)</h4>

    <ul class="nav nav-tabs mb-3">
      <li class="nav-item">
        <button class="nav-link" :class="{ active: tab === 'reports' }" @click="tab = 'reports'">
          ตรวจสอบคำร้องเรียน ({{ reports.length }})
        </button>
      </li>
      <li class="nav-item">
        <button class="nav-link" :class="{ active: tab === 'blacklist' }" @click="tab = 'blacklist'">
          จัดการ Blacklist ({{ blacklist.length }})
        </button>
      </li>
      <li class="nav-item">
        <button class="nav-link" :class="{ active: tab === 'users' }" @click="tab = 'users'">
          จัดการสมาชิก ({{ users.length }})
        </button>
      </li>
    </ul>

    <div v-if="statusMsg" class="alert alert-success py-2">{{ statusMsg }}</div>

    <!-- แท็บตรวจสอบคำร้องเรียน -->
    <div v-if="tab === 'reports'" class="table-responsive">
      <table class="table table-bordered table-striped align-middle">
        <thead class="table-dark">
          <tr>
            <th class="text-center" style="width: 60px">#</th>
            <th>ผู้แจ้ง</th>
            <th>ผู้ขายที่ถูกรายงาน</th>
            <th>เหตุผล / รายละเอียด</th>
            <th>หลักฐาน</th>
            <th class="text-center">สถานะ</th>
            <th class="text-center" style="width: 175px">การพิจารณา</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="rep in reports" :key="rep.report_id">
            <td class="text-center">{{ rep.report_id }}</td>
            <td>{{ rep.reporter_name }}</td>
            <td>{{ rep.seller_name }} (@{{ rep.seller_username }})</td>
            <td>
              <strong>{{ rep.reason_type }}</strong><br />
              <span class="small text-muted">{{ rep.detail }}</span>
            </td>
            <td class="small"><code>{{ rep.evidence_file || '-' }}</code></td>
            <td class="text-center">
              <span
                class="badge"
                :class="
                  rep.status === 'อนุมัติ'
                    ? 'bg-danger'
                    : rep.status === 'ปฏิเสธ'
                    ? 'bg-secondary'
                    : 'bg-warning text-dark'
                "
              >
                {{ rep.status }}
              </span>
            </td>
            <td class="text-center">
              <template v-if="rep.status === 'รอตรวจสอบ'">
                <button class="btn btn-danger btn-sm me-1" @click="reviewReport(rep, 'อนุมัติ')">
                  อนุมัติ (Blacklist)
                </button>
                <button class="btn btn-outline-secondary btn-sm" @click="reviewReport(rep, 'ปฏิเสธ')">
                  ปฏิเสธ
                </button>
              </template>
              <span v-else class="small text-muted">พิจารณาแล้ว</span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- แท็บจัดการ Blacklist -->
    <div v-else-if="tab === 'blacklist'" class="table-responsive">
      <table class="table table-bordered table-striped align-middle">
        <thead class="table-danger">
          <tr>
            <th class="text-center" style="width: 85px">User ID</th>
            <th>ผู้ขาย</th>
            <th>เหตุผล</th>
            <th class="text-center" style="width: 140px">จัดการ</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="b in blacklist" :key="b.blacklist_id">
            <td class="text-center">{{ b.seller_id }}</td>
            <td>{{ b.seller_name }} (@{{ b.seller_username }})</td>
            <td>{{ b.reason }}</td>
            <td class="text-center">
              <button
                class="btn btn-outline-success btn-sm"
                @click="removeFromBlacklist(b.seller_id, b.seller_name)"
              >
                ปลด Blacklist
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- แท็บจัดการสมาชิก -->
    <div v-else class="table-responsive">
      <table class="table table-bordered table-striped align-middle">
        <thead class="table-dark">
          <tr>
            <th class="text-center" style="width: 75px">ID</th>
            <th>ชื่อผู้ใช้</th>
            <th>ชื่อ-นามสกุล</th>
            <th>อีเมล</th>
            <th>เบอร์โทร</th>
            <th class="text-center">สิทธิ์ (Role)</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="u in users" :key="u.user_id">
            <td class="text-center">{{ u.user_id }}</td>
            <td>@{{ u.username }}</td>
            <td>{{ u.fullname }}</td>
            <td>{{ u.email }}</td>
            <td>{{ u.phone }}</td>
            <td class="text-center">
              <span class="badge bg-secondary">{{ u.role }}</span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
