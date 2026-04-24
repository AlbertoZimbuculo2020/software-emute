<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { 
    CalendarDays, Plus, Search, Trash2, Edit, Save, 
    X, ClipboardList, CreditCard, Activity, ChevronRight,
    ArrowUpDown
} from 'lucide-vue-next';

const props = defineProps({
    consultas: Array
});

const searchTerm = ref('');
const isEditing = ref(false);
const editingId = ref(null);

const form = useForm({
    descricao: '',
    valor: 0,
});

const filteredConsultas = computed(() => {
    if (!searchTerm.value) return props.consultas;
    return props.consultas.filter(c => 
        c.Descricao.toLowerCase().includes(searchTerm.value.toLowerCase()) ||
        c.Codigo.toLowerCase().includes(searchTerm.value.toLowerCase())
    );
});

const editConsulta = (consulta) => {
    isEditing.value = true;
    editingId.value = consulta.Id;
    form.descricao = consulta.Descricao;
    form.valor = consulta.Valor;
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

const resetForm = () => {
    isEditing.value = false;
    editingId.value = null;
    form.reset();
};

const salvarConsulta = () => {
    if (isEditing.value) {
        form.put(route('consultas.update', editingId.value), {
            onSuccess: () => resetForm()
        });
    } else {
        form.post(route('consultas.store'), {
            onSuccess: () => resetForm()
        });
    }
};

const excluirConsulta = (id) => {
    if (confirm('Deseja realmente remover este tipo de consulta?')) {
        router.delete(route('consultas.destroy', id));
    }
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('pt-AO', { style: 'currency', currency: 'AOA' }).format(value);
};
</script>

<template>
    <Head title="Tipos de Consultas" />

    <DashboardLayout>
        <div class="min-h-screen bg-[#f8fafc] p-4 lg:p-8">
            <div class="max-w-[1400px] mx-auto space-y-8">
                
                <!-- Header -->
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-6 rounded-3xl shadow-sm border border-slate-200/60">
                    <div>
                        <h1 class="text-2xl font-black text-slate-900 tracking-tight flex items-center gap-3">
                            <div class="p-2 bg-indigo-600 rounded-xl text-white shadow-lg shadow-indigo-200">
                                <CalendarDays class="w-6 h-6" />
                            </div>
                            TIPOS DE CONSULTAS
                        </h1>
                        <p class="text-slate-500 text-sm font-medium mt-1 ml-11">Configuração de Preçário e Serviços Clínicos</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                    <!-- Formulário de Cadastro (Esquerda/Topo) -->
                    <div class="lg:col-span-5">
                        <div class="bg-white p-8 rounded-[2.5rem] shadow-2xl shadow-slate-200/50 border border-slate-100 sticky top-24">
                            <div class="flex items-center gap-4 mb-8">
                                <div class="p-3 bg-indigo-50 rounded-2xl text-indigo-600">
                                    <Plus v-if="!isEditing" class="w-5 h-5" />
                                    <Edit v-else class="w-5 h-5" />
                                </div>
                                <h2 class="text-xl font-black text-slate-800 tracking-tight">
                                    {{ isEditing ? 'EDITAR CONSULTA' : 'NOVA CONSULTA' }}
                                </h2>
                            </div>

                            <div class="space-y-6">
                                <div class="space-y-2">
                                    <label class="text-[10px] font-black text-slate-400 uppercase ml-1 tracking-widest">Descrição da Consulta</label>
                                    <div class="relative group">
                                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none group-focus-within:text-indigo-500 text-slate-300 transition-colors">
                                            <ClipboardList class="w-4 h-4" />
                                        </div>
                                        <input v-model="form.descricao" placeholder="EX: CLINICA GERAL" class="w-full bg-slate-50 border-transparent focus:bg-white focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 rounded-2xl pl-11 pr-4 py-3.5 text-sm font-bold text-slate-700 transition-all uppercase placeholder:text-slate-300" />
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <label class="text-[10px] font-black text-slate-400 uppercase ml-1 tracking-widest">Valor do Serviço (Kz)</label>
                                    <div class="relative group">
                                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none group-focus-within:text-indigo-500 text-slate-300 transition-colors">
                                            <CreditCard class="w-4 h-4" />
                                        </div>
                                        <input type="number" v-model="form.valor" placeholder="0.00" class="w-full bg-slate-50 border-transparent focus:bg-white focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 rounded-2xl pl-11 pr-4 py-3.5 text-sm font-bold text-slate-700 transition-all placeholder:text-slate-300" />
                                    </div>
                                </div>

                                <div class="pt-4 flex flex-col gap-3">
                                    <button @click="salvarConsulta" :disabled="form.processing" class="w-full flex items-center justify-center px-10 py-4 bg-indigo-600 text-white rounded-2xl font-black text-[11px] tracking-widest hover:bg-indigo-700 transition-all shadow-xl shadow-indigo-200 disabled:opacity-50 uppercase">
                                        <Save class="w-4 h-4 mr-2" /> {{ isEditing ? 'Atualizar Dados' : 'Cadastrar Consulta' }}
                                    </button>
                                    <button v-if="isEditing" @click="resetForm" class="w-full py-3.5 bg-slate-100 text-slate-500 rounded-2xl font-black text-[11px] tracking-widest hover:bg-slate-200 transition-all uppercase">
                                        Cancelar Edição
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Lista de Consultas (Direita) -->
                    <div class="lg:col-span-7 space-y-6">
                        <div class="flex items-center justify-between bg-white p-4 rounded-2xl shadow-sm border border-slate-100">
                            <div class="flex items-center px-4 bg-slate-50 rounded-xl border border-slate-100 w-full max-w-md">
                                <Search class="w-4 h-4 text-slate-400 mr-3" />
                                <input v-model="searchTerm" placeholder="Filtrar por nome ou código..." class="bg-transparent border-none focus:ring-0 text-sm font-bold text-slate-600 placeholder:text-slate-300 py-2.5 w-full" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4">
                            <div v-for="consulta in filteredConsultas" :key="consulta.Id" class="group bg-white p-5 rounded-[2rem] shadow-xl shadow-slate-200/30 border border-slate-100 hover:border-indigo-200 transition-all flex items-center justify-between overflow-hidden relative">
                                <div class="flex items-center gap-5 relative z-10">
                                    <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-black text-xs shadow-inner">
                                        {{ consulta.Codigo }}
                                    </div>
                                    <div>
                                        <h3 class="text-sm font-black text-slate-800 uppercase tracking-tight">{{ consulta.Descricao }}</h3>
                                        <p class="text-[11px] font-bold text-indigo-600 mt-1">{{ formatCurrency(consulta.Valor) }}</p>
                                    </div>
                                </div>
                                
                                <div class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-all transform translate-x-4 group-hover:translate-x-0">
                                    <button @click="editConsulta(consulta)" class="p-2.5 bg-indigo-50 text-indigo-600 rounded-xl hover:bg-indigo-100 transition-colors">
                                        <Edit class="w-4 h-4" />
                                    </button>
                                    <button @click="excluirConsulta(consulta.Id)" class="p-2.5 bg-red-50 text-red-500 rounded-xl hover:bg-red-100 transition-colors">
                                        <Trash2 class="w-4 h-4" />
                                    </button>
                                </div>

                                <!-- Efeito de fundo decorativo -->
                                <div class="absolute -right-4 -bottom-4 opacity-[0.03] group-hover:opacity-[0.07] transition-opacity pointer-events-none">
                                    <CalendarDays class="w-24 h-24" />
                                </div>
                            </div>

                            <div v-if="filteredConsultas.length === 0" class="p-20 text-center bg-white rounded-[3rem] border border-dashed border-slate-200">
                                <div class="inline-flex p-4 bg-slate-50 rounded-full mb-4">
                                    <Search class="w-8 h-8 text-slate-300" />
                                </div>
                                <h3 class="text-sm font-black text-slate-400 uppercase tracking-widest">Nenhum resultado encontrado</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>
