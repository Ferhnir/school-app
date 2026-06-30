<script setup>
import CreateUserForm from '@/Pages/Admin/Partials/CreateUserForm.vue';
import Modal from '@/Components/Modal.vue';

defineProps({
    show: Boolean,
    roles: Array,
});

const emit = defineEmits(['close']);
</script>

<template>
    <Modal :show="show" @close="emit('close')">
        <div class="p-6">
            <CreateUserForm :roles="roles" @success="emit('close')" />
        </div>
    </Modal>
</template>
