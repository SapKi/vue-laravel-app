<template>
  <div id="app">
    <nav class="navbar">
      <router-link to="/" class="nav-brand">Review Queue</router-link>
      <div class="nav-links">
        <router-link to="/">Queue</router-link>
        <router-link to="/submit" class="nav-submit">+ Submit</router-link>
      </div>
    </nav>
    <main class="main-content">
      <router-view />
    </main>
  </div>
</template>

<script setup>
</script>

<style>
* {
  box-sizing: border-box;
  margin: 0;
  padding: 0;
}

body {
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  background-color: #f5f5f5;
  color: #333;
}

.navbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.85rem 2rem;
  background-color: #1e1b4b;
  color: white;
  box-shadow: 0 2px 6px rgba(0,0,0,0.15);
}

.nav-brand {
  font-size: 1.15rem;
  font-weight: 700;
  color: white;
  text-decoration: none;
  letter-spacing: -0.01em;
}

.nav-links {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.nav-links a {
  color: rgba(255,255,255,0.85);
  text-decoration: none;
  padding: 0.35rem 0.85rem;
  border-radius: 6px;
  font-size: 0.9rem;
  transition: background-color 0.15s;
}

.nav-links a:hover,
.nav-links a.router-link-active {
  background-color: rgba(255,255,255,0.15);
  color: white;
}

.nav-submit {
  background-color: #4f46e5 !important;
  color: white !important;
  font-weight: 600;
}
.nav-submit:hover { background-color: #4338ca !important; }

.main-content {
  max-width: 900px;
  margin: 2rem auto;
  padding: 0 1rem;
}
</style>
