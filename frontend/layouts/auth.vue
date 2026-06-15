<template>
  <div class="flex min-h-screen flex-col">
    <AppHeader />
    <div class="flex flex-1 items-center justify-center px-4 py-12 sm:px-6 lg:px-8">
      <div class="w-full max-w-md">
        <slot />
      </div>
    </div>
    <AppFooter />
  </div>
</template>
