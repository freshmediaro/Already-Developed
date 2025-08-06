<template>
  <Head title="Desktop" />
  
  <DesktopLayout is-desktop-mode>
    <!-- Desktop content is handled by the layout -->
  </DesktopLayout>
</template>

<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import DesktopLayout from '@/Layouts/DesktopLayout.vue'

// Props passed from Laravel controller
interface Props {
  user: any
  currentTeam: any
  allTeams: any[]
  userPreferences: any
  installedApps: any[]
  desktopConfig: any
}

defineProps<Props>()
</script> 