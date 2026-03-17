<template>
  <div id="app">
    <nav class="navbar">
      <router-link to="/" class="nav-brand">
        Review Queue
      </router-link>
      <div class="nav-links">
        <router-link to="/" class="nav-link">Queue</router-link>
      </div>
    </nav>
    <main class="main-content">
      <router-view v-slot="{ Component }">
        <transition name="page" mode="out-in">
          <component :is="Component" />
        </transition>
      </router-view>
    </main>
  </div>
</template>

<script setup>
</script>

<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

*, *::before, *::after {
  box-sizing: border-box;
  margin: 0;
  padding: 0;
}

body {
  font-family: 'Inter', 'Segoe UI', sans-serif;
  background: linear-gradient(135deg, #f0f0ff 0%, #fafaff 50%, #f0f9ff 100%);
  min-height: 100vh;
  color: #1e1b4b;
}

.navbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0 2rem;
  height: 60px;
  background: linear-gradient(90deg, #1e1b4b 0%, #312e81 50%, #4c1d95 100%);
  box-shadow: 0 4px 24px rgba(79, 70, 229, 0.35);
  position: sticky;
  top: 0;
  z-index: 50;
}

.nav-brand {
  font-size: 1.1rem;
  font-weight: 800;
  color: #fff;
  text-decoration: none;
  letter-spacing: -0.02em;
}

.nav-links {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.nav-link {
  color: rgba(255,255,255,0.75);
  text-decoration: none;
  padding: 0.4rem 1rem;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 500;
  transition: all 0.2s;
}
.nav-link:hover,
.nav-link.router-link-active {
  background: rgba(255,255,255,0.15);
  color: #fff;
}


.main-content {
  max-width: 920px;
  margin: 2.5rem auto;
  padding: 0 1.25rem;
}

/* Page transition */
.page-enter-active,
.page-leave-active {
  transition: opacity 0.2s, transform 0.2s;
}
.page-enter-from {
  opacity: 0;
  transform: translateY(10px);
}
.page-leave-to {
  opacity: 0;
  transform: translateY(-6px);
}
</style>
