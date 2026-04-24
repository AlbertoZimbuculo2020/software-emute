<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { KeyRound, Save, Lock } from 'lucide-vue-next';

const form = useForm({
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('configuracoes.senha.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
};
</script>

<template>
    <Head title="Alterar Senha" />

    <DashboardLayout>
        <div class="min-h-screen bg-[#F4F7FA] p-4 lg:p-8 flex items-center justify-center">
            <div class="max-w-2xl w-full">
                <!-- Header Bento -->
                <div class="bg-white rounded-[2.5rem] p-8 shadow-sm border border-slate-200/60 mb-8 flex flex-col md:flex-row justify-between items-center gap-6">
                    <div class="flex items-center gap-6">
                        <div class="w-16 h-16 bg-blue-600 rounded-2xl flex items-center justify-center text-white shadow-xl shadow-blue-200">
                            <KeyRound class="w-8 h-8" />
                        </div>
                        <div>
                            <h1 class="text-xl font-black text-slate-900 uppercase tracking-tight">Alterar Senha</h1>
                            <p class="text-[10px] font-bold text-slate-400 mt-1 uppercase tracking-widest">Atualize as suas credenciais de acesso ao sistema</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-[3rem] p-10 shadow-sm border border-slate-200/60">
                    <form @submit.prevent="submit" class="space-y-6">
                        <div class="space-y-2">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Nova Senha</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <Lock class="w-5 h-5 text-slate-400" />
                                </div>
                                <input v-model="form.password" type="password" required
                                       class="w-full pl-12 bg-slate-50 border-transparent focus:bg-white focus:ring-4 focus:ring-blue-500/10 rounded-2xl p-4 text-sm font-bold transition-all"
                                       placeholder="••••••••">
                            </div>
                            <p v-if="form.errors.password" class="text-xs text-red-500 font-bold ml-1">{{ form.errors.password }}</p>
                        </div>

                        <div class="space-y-2">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Confirme a Senha</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <Lock class="w-5 h-5 text-slate-400" />
                                </div>
                                <input v-model="form.password_confirmation" type="password" required
                                       class="w-full pl-12 bg-slate-50 border-transparent focus:bg-white focus:ring-4 focus:ring-blue-500/10 rounded-2xl p-4 text-sm font-bold transition-all"
                                       placeholder="••••••••">
                            </div>
                        </div>

                        <div class="pt-6">
                            <button type="submit" :disabled="form.processing"
                                    class="w-full bg-blue-600 text-white py-4 rounded-2xl text-xs font-black uppercase tracking-[0.2em] hover:bg-blue-700 transition-all shadow-xl shadow-blue-200 flex items-center justify-center gap-3 disabled:opacity-50">
                                <Save class="w-5 h-5" /> Gravar Nova Senha
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>

<style scoped>
input:focus {
    outline: none;
}
</style>
