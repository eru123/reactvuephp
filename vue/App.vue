<template>
  <div style="text-align: center; margin-top: 50px; font-family: sans-serif;">
    <h1>Vue + JavaScript</h1>
    <div class="card">
      <button type="button" @click="count++">count is {{ count }}</button>
    </div>
    <p>
      Check out the <a href="/react">React App</a>
    </p>
  </div>
</template>

<script setup>
import { ref } from 'vue'
const count = ref(0)
</script>
