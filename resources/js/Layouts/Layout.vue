<script setup>
import { Head, usePage } from '@inertiajs/vue3'

const page = usePage()
</script>

<template>
  <Head>
    <title>{{ page.props.system?.name }}</title>
    <meta name="description" content="Modern POS System" />
  </Head>

  <slot />
</template>
