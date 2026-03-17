<template>
  <div id="app">
    <nav class="navbar">
      <router-link to="/" class="nav-brand">Review Queue</router-link>
      <div class="nav-links">
        <router-link to="/" class="nav-link">Queue</router-link>
        <button class="theme-btn" :title="isDark ? 'Switch to light mode' : 'Switch to dark mode'" @click="toggleDark">
          {{ isDark ? '☀️' : '🌙' }}
        </button>
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
import { ref, watchEffect } from 'vue';

const isDark = ref(localStorage.getItem('theme') === 'dark');

function toggleDark() {
  isDark.value = !isDark.value;
  localStorage.setItem('theme', isDark.value ? 'dark' : 'light');
}

watchEffect(() => {
  document.documentElement.classList.toggle('dark', isDark.value);
});
</script>

<style>
/* ── CSS custom properties ─────────────────────────────────── */
:root {
  --bg:            #f0f9ff;
  --bg-card:       #ffffff;
  --bg-input:      #f8fafc;
  --bg-hover:      #eff6ff;
  --border:        #bae6fd;
  --border-focus:  #3b82f6;
  --primary:       #2563eb;
  --primary-2:     #3b82f6;
  --primary-grad:  linear-gradient(135deg, #2563eb, #3b82f6);
  --primary-light: #eff6ff;
  --text:          #0f172a;
  --text-muted:    #475569;
  --text-faint:    #94a3b8;
  --shadow-sm:     0 2px 10px rgba(37,99,235,0.08);
  --shadow-md:     0 8px 28px rgba(37,99,235,0.14);
  --nav-bg:        linear-gradient(90deg, #0c1a3a 0%, #1e3a8a 60%, #1d4ed8 100%);
}

html.dark {
  --bg:            #0b1120;
  --bg-card:       #1e293b;
  --bg-input:      #0f172a;
  --bg-hover:      #1e3a5f;
  --border:        #334155;
  --border-focus:  #3b82f6;
  --primary:       #3b82f6;
  --primary-2:     #60a5fa;
  --primary-grad:  linear-gradient(135deg, #2563eb, #60a5fa);
  --primary-light: #172554;
  --text:          #e2e8f0;
  --text-muted:    #94a3b8;
  --text-faint:    #475569;
  --shadow-sm:     0 2px 10px rgba(0,0,0,0.35);
  --shadow-md:     0 8px 28px rgba(0,0,0,0.45);
  --nav-bg:        linear-gradient(90deg, #020617 0%, #0f172a 60%, #1e293b 100%);
}

/* ── Reset ─────────────────────────────────────────────────── */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

body {
  font-family: 'Inter', 'Segoe UI', sans-serif;
  background: var(--bg);
  color: var(--text);
  min-height: 100vh;
  transition: background 0.25s, color 0.25s;
}

/* ── Navbar ────────────────────────────────────────────────── */
.navbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0 2rem;
  height: 58px;
  background: var(--nav-bg);
  box-shadow: 0 2px 16px rgba(29,78,216,0.3);
  position: sticky;
  top: 0;
  z-index: 50;
}

.nav-brand {
  font-size: 1.05rem;
  font-weight: 800;
  color: #fff;
  text-decoration: none;
  letter-spacing: -0.02em;
}

.nav-links { display: flex; align-items: center; gap: 0.5rem; }

.nav-link {
  color: rgba(255,255,255,0.75);
  text-decoration: none;
  padding: 0.4rem 1rem;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 500;
  transition: all 0.15s;
}
.nav-link:hover, .nav-link.router-link-active {
  background: rgba(255,255,255,0.15);
  color: #fff;
}

.theme-btn {
  width: 34px;
  height: 34px;
  border-radius: 8px;
  border: 1px solid rgba(255,255,255,0.2);
  background: rgba(255,255,255,0.1);
  cursor: pointer;
  font-size: 1rem;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.15s;
}
.theme-btn:hover { background: rgba(255,255,255,0.22); }

/* ── Layout ────────────────────────────────────────────────── */
.main-content {
  max-width: 920px;
  margin: 2.5rem auto;
  padding: 0 1.25rem;
}

/* ── Page transition ───────────────────────────────────────── */
.page-enter-active, .page-leave-active { transition: opacity 0.2s, transform 0.2s; }
.page-enter-from  { opacity: 0; transform: translateY(10px); }
.page-leave-to    { opacity: 0; transform: translateY(-6px); }
</style>
