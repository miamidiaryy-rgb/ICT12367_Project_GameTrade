<script setup>
import { ref } from 'vue'
import { API_BASE_URL } from '../config'

const emit = defineEmits(['login-success'])

const isRegister = ref(false)
const username = ref('')
const password = ref('')
const fullname = ref('')
const email = ref('')
const phone = ref('')
const role = ref('buyer')
const msg = ref('')
const err = ref('')

const submitAuth = async () => {
  msg.value = ''
  err.value = ''
  const payload = isRegister.value
    ? { action: 'register', username: username.value, password: password.value, fullname: fullname.value, email: email.value, phone: phone.value, role: role.value }
    : { action: 'login', username: username.value, password: password.value }

  const res = await fetch(`${API_BASE_URL}/auth_api.php`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload),
  })
  const json = await res.json()
  if (json.success) {
    if (isRegister.value) {
      msg.value = 'สมัครสมาชิกสำเร็จ สามารถเข้าสู่ระบบได้ทันที'
      isRegister.value = false
    } else {
      emit('login-success', json.user)
    }
  } else {
    err.value = json.message || 'ดำเนินการไม่สำเร็จ'
  }
}
</script>

<template>
  <div class="container mt-4 mb-5">
    <div class="card mx-auto" style="max-width: 460px">
      <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <h5 class="mb-0">{{ isRegister ? 'สมัครสมาชิก GameTrade' : 'เข้าสู่ระบบ GameTrade' }}</h5>
        <button class="btn btn-link btn-sm p-0" @click="isRegister = !isRegister; err = ''; msg = ''">
          {{ isRegister ? 'มีบัญชีแล้ว? เข้าสู่ระบบ' : 'ยังไม่มีบัญชี? สมัครสมาชิก' }}
        </button>
      </div>
      <div class="card-body">
        <div v-if="msg" class="alert alert-success py-2">{{ msg }}</div>
        <div v-if="err" class="alert alert-danger py-2">{{ err }}</div>

        <form @submit.prevent="submitAuth">
          <div class="mb-3">
            <label class="form-label">ชื่อผู้ใช้ (Username)</label>
            <input v-model="username" type="text" class="form-control" required />
          </div>
          <div class="mb-3">
            <label class="form-label">รหัสผ่าน (Password)</label>
            <input v-model="password" type="password" class="form-control" required />
          </div>

          <template v-if="isRegister">
            <div class="mb-3">
              <label class="form-label">ชื่อ-นามสกุล</label>
              <input v-model="fullname" type="text" class="form-control" required />
            </div>
            <div class="row mb-3">
              <div class="col-6">
                <label class="form-label">อีเมล</label>
                <input v-model="email" type="email" class="form-control" required />
              </div>
              <div class="col-6">
                <label class="form-label">เบอร์โทรศัพท์</label>
                <input v-model="phone" type="text" class="form-control" />
              </div>
            </div>
            <div class="mb-3">
              <label class="form-label">ประเภทบัญชี</label>
              <select v-model="role" class="form-select">
                <option value="buyer">ผู้ซื้อ (Buyer)</option>
                <option value="seller">ผู้ขาย (Seller)</option>
              </select>
            </div>
          </template>

          <button type="submit" class="btn btn-primary w-100">
            {{ isRegister ? 'สมัครสมาชิก' : 'เข้าสู่ระบบ' }}
          </button>
        </form>

        <hr />
        <div class="small text-muted">
          <div>บัญชีทดสอบ (รหัสผ่าน <code>123456</code> ทุกบัญชี):</div>
          <div>ผู้ซื้อ: <code>buyer01</code> | ผู้ขาย: <code>seller01</code> | ผู้ดูแลระบบ: <code>admin</code></div>
        </div>
      </div>
    </div>
  </div>
</template>
