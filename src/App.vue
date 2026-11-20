<script setup>
import { ref } from 'vue'
import MarketplaceView from './components/MarketplaceView.vue'
import SellerView from './components/SellerView.vue'
import OrdersView from './components/OrdersView.vue'
import BlacklistView from './components/BlacklistView.vue'
import AdminView from './components/AdminView.vue'
import AuthView from './components/AuthView.vue'

const currentUser = ref(null)
const page = ref('market') // 'market' | 'seller' | 'orders' | 'blacklist' | 'admin' | 'auth'

const onLoginSuccess = (u) => {
  currentUser.value = u
  page.value = u.role === 'admin' ? 'admin' : 'market'
}

const logout = () => {
  currentUser.value = null
  page.value = 'market'
}
</script>

<template>
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
      <a class="navbar-brand fw-semibold" href="#" @click.prevent="page = 'market'">GameTrade</a>
      <div class="collapse navbar-collapse show">
        <ul class="navbar-nav me-auto">
          <li class="nav-item">
            <a href="#" class="nav-link" :class="{ active: page === 'market' }" @click.prevent="page = 'market'">
              ตลาดไอเทม
            </a>
          </li>
          <li class="nav-item">
            <a href="#" class="nav-link" :class="{ active: page === 'blacklist' }" @click.prevent="page = 'blacklist'">
              ตรวจสอบ Blacklist
            </a>
          </li>
          <li v-if="currentUser && (currentUser.role === 'seller' || currentUser.role === 'admin')" class="nav-item">
            <a href="#" class="nav-link" :class="{ active: page === 'seller' }" @click.prevent="page = 'seller'">
              จัดการประกาศขาย
            </a>
          </li>
          <li v-if="currentUser" class="nav-item">
            <a href="#" class="nav-link" :class="{ active: page === 'orders' }" @click.prevent="page = 'orders'">
              ประวัติการซื้อขาย
            </a>
          </li>
          <li v-if="currentUser && currentUser.role === 'admin'" class="nav-item">
            <a href="#" class="nav-link" :class="{ active: page === 'admin' }" @click.prevent="page = 'admin'">
              Admin Dashboard
            </a>
          </li>
        </ul>

        <div class="d-flex align-items-center gap-2">
          <template v-if="currentUser">
            <span class="navbar-text small text-light me-2">
              {{ currentUser.fullname }} ({{ currentUser.role }})
            </span>
            <button class="btn btn-outline-light btn-sm" @click="logout">ออกจากระบบ</button>
          </template>
          <button v-else class="btn btn-primary btn-sm" @click="page = 'auth'">เข้าสู่ระบบ / สมัครสมาชิก</button>
        </div>
      </div>
    </div>
  </nav>

  <MarketplaceView v-if="page === 'market'" :user="currentUser" />
  <BlacklistView v-else-if="page === 'blacklist'" />
  <SellerView v-else-if="page === 'seller' && currentUser" :user="currentUser" />
  <OrdersView v-else-if="page === 'orders' && currentUser" :user="currentUser" />
  <AdminView v-else-if="page === 'admin' && currentUser" :user="currentUser" />
  <AuthView v-else @login-success="onLoginSuccess" />
</template>

<style>
body {
  font-family: 'Leelawadee UI', Tahoma, sans-serif;
  background-color: #ffffff;
}
</style>
