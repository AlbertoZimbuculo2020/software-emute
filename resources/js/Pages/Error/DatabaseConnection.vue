<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    message: String,
});
</script>

<template>
    <GuestLayout>
        <Head title="Erro de Ligação" />

        <div class="mb-4 text-sm text-red-600">
            {{ message || 'A conexão não foi feita ao banco de dados.' }}
        </div>

        <div class="flex items-center justify-end mt-4">
            <p class="text-sm text-gray-600">
                Por favor, verifique se o banco de dados foi inicializado corretamente ou contacte o administrador.
            </p>
        </div>
    </GuestLayout>
</template>
