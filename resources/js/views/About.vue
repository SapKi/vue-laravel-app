<template>
  <div class="about">
    <h1>About</h1>
    <div class="card">
      <h2>Stack</h2>
      <ul>
        <li><strong>Frontend:</strong> Vue 3 (Composition API) + Vite</li>
        <li><strong>State:</strong> Pinia</li>
        <li><strong>Routing:</strong> Vue Router 4</li>
        <li><strong>HTTP:</strong> Axios</li>
        <li><strong>Backend:</strong> Laravel 12</li>
        <li><strong>Auth:</strong> Laravel Sanctum</li>
        <li><strong>Database:</strong> SQLite (default)</li>
      </ul>
    </div>
  </div>
</template>

<style scoped>
.about {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

h1 {
  font-size: 2rem;
  color: #4f46e5;
}

.card {
  background: white;
  border-radius: 8px;
  padding: 1.5rem;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.card h2 {
  margin-bottom: 1rem;
}

ul {
  list-style: none;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

li {
  padding: 0.5rem;
  background: #f9fafb;
  border-radius: 4px;
}
</style>
