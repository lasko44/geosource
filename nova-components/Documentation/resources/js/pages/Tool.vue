<template>
  <div>
    <Head title="Developer Documentation" />

    <Heading class="mb-6">Developer Documentation</Heading>

    <Card class="p-0 overflow-hidden">
      <div class="documentation-wrapper" v-html="content"></div>
    </Card>
  </div>
</template>

<script>
export default {
  props: {
    content: {
      type: String,
      default: ''
    }
  },
  mounted() {
    // Handle anchor clicks for smooth scrolling within Nova
    this.$el.addEventListener('click', (e) => {
      const link = e.target.closest('a[href^="#"]');
      if (link) {
        e.preventDefault();
        const id = link.getAttribute('href').slice(1);
        const element = document.getElementById(id);
        if (element) {
          element.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
      }
    });
  }
}
</script>

<style>
.documentation-wrapper {
  background: white;
}

/* Dark mode support */
.dark .documentation-wrapper {
  background: rgb(30 41 59);
}

.dark .documentation-wrapper .bg-white {
  background: rgb(30 41 59) !important;
}

.dark .documentation-wrapper .bg-gray-50,
.dark .documentation-wrapper .bg-gray-100 {
  background: rgb(51 65 85) !important;
}

.dark .documentation-wrapper .text-gray-900,
.dark .documentation-wrapper .text-gray-800,
.dark .documentation-wrapper .text-gray-700 {
  color: rgb(226 232 240) !important;
}

.dark .documentation-wrapper .text-gray-600,
.dark .documentation-wrapper .text-gray-500 {
  color: rgb(148 163 184) !important;
}

.dark .documentation-wrapper .border-gray-200,
.dark .documentation-wrapper .border-gray-100 {
  border-color: rgb(51 65 85) !important;
}

.dark .documentation-wrapper code {
  background: rgb(51 65 85) !important;
  color: rgb(226 232 240) !important;
}

.dark .documentation-wrapper pre {
  background: rgb(15 23 42) !important;
}

.dark .documentation-wrapper pre code {
  background: transparent !important;
}

.dark .documentation-wrapper .divide-y > * + * {
  border-color: rgb(51 65 85) !important;
}

/* Colored boxes dark mode */
.dark .documentation-wrapper .bg-purple-50 {
  background: rgba(147, 51, 234, 0.1) !important;
}
.dark .documentation-wrapper .bg-blue-50 {
  background: rgba(59, 130, 246, 0.1) !important;
}
.dark .documentation-wrapper .bg-green-50 {
  background: rgba(34, 197, 94, 0.1) !important;
}
.dark .documentation-wrapper .bg-yellow-50 {
  background: rgba(234, 179, 8, 0.1) !important;
}
.dark .documentation-wrapper .bg-red-50 {
  background: rgba(239, 68, 68, 0.1) !important;
}
.dark .documentation-wrapper .bg-orange-50 {
  background: rgba(249, 115, 22, 0.1) !important;
}

.dark .documentation-wrapper .text-purple-900,
.dark .documentation-wrapper .text-purple-800 {
  color: rgb(216, 180, 254) !important;
}
.dark .documentation-wrapper .text-blue-900,
.dark .documentation-wrapper .text-blue-800 {
  color: rgb(147, 197, 253) !important;
}
.dark .documentation-wrapper .text-green-900,
.dark .documentation-wrapper .text-green-800 {
  color: rgb(134, 239, 172) !important;
}
.dark .documentation-wrapper .text-yellow-900,
.dark .documentation-wrapper .text-yellow-800,
.dark .documentation-wrapper .text-yellow-700 {
  color: rgb(253, 224, 71) !important;
}
.dark .documentation-wrapper .text-red-900,
.dark .documentation-wrapper .text-red-800,
.dark .documentation-wrapper .text-red-700 {
  color: rgb(252, 165, 165) !important;
}
.dark .documentation-wrapper .text-orange-900,
.dark .documentation-wrapper .text-orange-800,
.dark .documentation-wrapper .text-orange-700 {
  color: rgb(253, 186, 116) !important;
}
</style>
