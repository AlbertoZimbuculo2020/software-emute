<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { 
    UserPlus, 
    Users, 
    HeartPulse, 
    Stethoscope,
    Save,
    X,
    CheckCircle2,
    AlertCircle
} from 'lucide-vue-next';

const props = defineProps({
    consultas: { type: Array, default: () => [] },
    seguradoras: { type: Array, default: () => [] }
});

const page = usePage();
const flash = computed(() => page.props.value.flash);

const tipoSeleccionado = ref('paciente');

const form = useForm({
    tipo: 'paciente',
    nome: '',
    nif: '',
    telefone: '',
    email: '',
    cidade: '',
    rua: '',
    natureza: 'SINGULAR',
    data_nascimento: '',
    genero: '',
    seguradora: '',
    pai: '',
    mae: '',
    carteira_medica: '',
});

const tiposEntidade = [
    { value: 'cliente', label: 'Cliente', icon: Users, color: 'blue', description: 'Entidade ou empresa que utiliza os serviços' },
    { value: 'paciente', label: 'Paciente', icon: HeartPulse, color: 'red', description: 'Pessoa com processo clínico no hospital' },
    { value: 'medico', label: 'Médico', icon: Stethoscope, color: 'slate', description: 'Profissional de saúde do corpo clínico' },
];

const seleccionarTipo = (tipo) => {
    tipoSeleccionado.value = tipo;
    form.tipo = tipo;
    form.reset();
};

const submit = () => {
    form.post(route('entidades.registo.store'), {
        onSuccess: () => {
            form.reset();
        }
    });
};

const getColorClasses = (color) => {
    const colors = {
        blue: 'bg-blue-500 hover:bg-blue-600 shadow-blue-200',
        red: 'bg-red-500 hover:bg-red-600 shadow-red-200',
        slate: 'bg-slate-700 hover:bg-slate-800 shadow-slate-200',
    };
    return colors[color] || colors.blue;
};

const getActiveColorClasses = (color) => {
    const colors = {
        blue: 'bg-blue-50 border-blue-300 text-blue-700 ring-2 ring-blue-200',
        red: 'bg-red-50 border-red-300 text-red-700 ring-2 ring-red-200',
        slate: 'bg-slate-50 border-slate-400 text-slate-800 ring-2 ring-slate-200',
    };
    return colors[color] || colors.blue;
};
</script>

<template>
    <Head title="Registar Entidade" />

    <DashboardLayout>
        <div class="min-h-screen bg-[#f8fafc] p-4 lg:p-8">
            <div class="max-w-4xl mx-auto space-y-8">
                
                <!-- Header -->
                <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200/60">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-gradient-to-br from-blue-600 to-indigo-600 rounded-2xl text-white shadow-lg shadow-blue-200">
                            <UserPlus class="w-6 h-6" />
                        </div>
                        <div>
                            <h1 class="text-2xl font-black text-slate-900 tracking-tight">REGISTAR ENTIDADE</h1>
                            <p class="text-slate-500 text-sm font-medium mt-1">Registo unificado de Clientes, Pacientes e Médicos</p>
                        </div>
                    </div>
                </div>

                <!-- Flash Messages -->
                <div v-if="flash?.success" class="bg-emerald-50 border border-emerald-200 rounded-2xl p-4 flex items-center gap-3">
                    <CheckCircle2 class="w-5 h-5 text-emerald-500 flex-shrink-0" />
                    <p class="text-sm font-bold text-emerald-700">{{ flash.success }}</p>
                </div>

                <div v-if="form.errors?.error" class="bg-red-50 border border-red-200 rounded-2xl p-4 flex items-center gap-3">
                    <AlertCircle class="w-5 h-5 text-red-500 flex-shrink-0" />
                    <p class="text-sm font-bold text-red-700">{{ form.errors.error }}</p>
                </div>

                <!-- Seleção de Tipo -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <button 
                        v-for="tipo in tiposEntidade" 
                        :key="tipo.value"
                        @click="seleccionarTipo(tipo.value)"
                        :class="tipoSeleccionado === tipo.value ? getActiveColorClasses(tipo.color) : 'bg-white border-slate-200 text-slate-500 hover:bg-slate-50'"
                        class="p-5 rounded-2xl border-2 transition-all text-left group"
                    >
                        <div class="flex items-center gap-3">
                            <div :class="tipoSeleccionado === tipo.value ? getColorClasses(tipo.color) : 'bg-slate-100 group-hover:bg-slate-200'" 
                                 class="p-2.5 rounded-xl text-white shadow-sm transition-colors">
                                <component :is="tipo.icon" class="w-5 h-5" />
                            </div>
                            <div>
                                <h3 class="text-sm font-black uppercase tracking-tight">{{ tipo.label }}</h3>
                                <p class="text-[10px] font-medium opacity-70 mt-0.5">{{ tipo.description }}</p>
                            </div>
                        </div>
                    </button>
                </div>

                <!-- Formulário -->
                <div class="bg-white p-8 rounded-[2rem] shadow-xl border border-slate-100">
                    <form @submit.prevent="submit" class="space-y-8">
                        
                        <!-- Dados Pessoais -->
                        <div class="space-y-5">
                            <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] border-b border-slate-100 pb-3">
                                Dados Principais
                            </h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div class="md:col-span-2">
                                    <label class="block text-[11px] font-black text-slate-500 uppercase tracking-wider mb-2">Nome Completo / Designação *</label>
                                    <input 
                                        v-model="form.nome" 
                                        required
                                        class="w-full bg-slate-50 border-transparent focus:bg-white focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 rounded-2xl px-5 py-3.5 text-sm font-bold text-slate-700 transition-all uppercase placeholder:text-slate-300" 
                                        placeholder="NOME DA ENTIDADE"
                                    />
                                    <span v-if="form.errors.nome" class="text-red-500 text-[10px] mt-1 block">{{ form.errors.nome }}</span>
                                </div>
                                
                                <div>
                                    <label class="block text-[11px] font-black text-slate-500 uppercase tracking-wider mb-2">NIF / Contribuinte</label>
                                    <input 
                                        v-model="form.nif" 
                                        class="w-full bg-slate-50 border-transparent focus:bg-white focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 rounded-2xl px-5 py-3.5 text-sm font-bold text-slate-700 transition-all placeholder:text-slate-300" 
                                        placeholder="999.999.999"
                                    />
                                </div>

                                <!-- Cliente: Natureza -->
                                <div v-if="tipoSeleccionado === 'cliente'">
                                    <label class="block text-[11px] font-black text-slate-500 uppercase tracking-wider mb-2">Natureza *</label>
                                    <select 
                                        v-model="form.natureza" 
                                        required
                                        class="w-full bg-slate-50 border-transparent focus:bg-white focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 rounded-2xl px-5 py-3.5 text-sm font-bold text-slate-700 transition-all"
                                    >
                                        <option value="SINGULAR">Singular (Indivíduo)</option>
                                        <option value="COLECTIVO">Colectivo (Empresa)</option>
                                    </select>
                                </div>

                                <!-- Paciente: Data Nascimento + Género -->
                                <template v-if="tipoSeleccionado === 'paciente'">
                                    <div>
                                        <label class="block text-[11px] font-black text-slate-500 uppercase tracking-wider mb-2">Data de Nascimento</label>
                                        <input 
                                            type="date" 
                                            v-model="form.data_nascimento" 
                                            class="w-full bg-slate-50 border-transparent focus:bg-white focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 rounded-2xl px-5 py-3.5 text-sm font-bold text-slate-700 transition-all"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-black text-slate-500 uppercase tracking-wider mb-2">Género</label>
                                        <select 
                                            v-model="form.genero" 
                                            class="w-full bg-slate-50 border-transparent focus:bg-white focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 rounded-2xl px-5 py-3.5 text-sm font-bold text-slate-700 transition-all"
                                        >
                                            <option value="">Selecione...</option>
                                            <option value="Masculino">Masculino</option>
                                            <option value="Feminino">Feminino</option>
                                        </select>
                                    </div>
                                </template>

                                <!-- Médico: Carteira Médica -->
                                <div v-if="tipoSeleccionado === 'medico'">
                                    <label class="block text-[11px] font-black text-slate-500 uppercase tracking-wider mb-2">Carteira Médica / Ordem</label>
                                    <input 
                                        v-model="form.carteira_medica" 
                                        class="w-full bg-slate-50 border-transparent focus:bg-white focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 rounded-2xl px-5 py-3.5 text-sm font-bold text-slate-700 transition-all uppercase placeholder:text-slate-300" 
                                        placeholder="REF-00000"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Filiação (Paciente) -->
                        <div v-if="tipoSeleccionado === 'paciente'" class="space-y-5">
                            <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] border-b border-slate-100 pb-3">
                                Filiação
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div>
                                    <label class="block text-[11px] font-black text-slate-500 uppercase tracking-wider mb-2">Nome do Pai</label>
                                    <input 
                                        v-model="form.pai" 
                                        class="w-full bg-slate-50 border-transparent focus:bg-white focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 rounded-2xl px-5 py-3.5 text-sm font-bold text-slate-700 transition-all uppercase placeholder:text-slate-300" 
                                        placeholder="NOME DO PAI"
                                    />
                                </div>
                                <div>
                                    <label class="block text-[11px] font-black text-slate-500 uppercase tracking-wider mb-2">Nome da Mãe</label>
                                    <input 
                                        v-model="form.mae" 
                                        class="w-full bg-slate-50 border-transparent focus:bg-white focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 rounded-2xl px-5 py-3.5 text-sm font-bold text-slate-700 transition-all uppercase placeholder:text-slate-300" 
                                        placeholder="NOME DA MÃE"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Contactos -->
                        <div class="space-y-5">
                            <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] border-b border-slate-100 pb-3">
                                Contactos e Localização
                            </h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div>
                                    <label class="block text-[11px] font-black text-slate-500 uppercase tracking-wider mb-2">Telefone / Telemóvel</label>
                                    <input 
                                        v-model="form.telefone" 
                                        class="w-full bg-slate-50 border-transparent focus:bg-white focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 rounded-2xl px-5 py-3.5 text-sm font-bold text-slate-700 transition-all placeholder:text-slate-300" 
                                        placeholder="+244 ..."
                                    />
                                </div>
                                
                                <div>
                                    <label class="block text-[11px] font-black text-slate-500 uppercase tracking-wider mb-2">Email</label>
                                    <input 
                                        type="email" 
                                        v-model="form.email" 
                                        class="w-full bg-slate-50 border-transparent focus:bg-white focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 rounded-2xl px-5 py-3.5 text-sm font-bold text-slate-700 transition-all placeholder:text-slate-300" 
                                        placeholder="email@exemplo.com"
                                    />
                                </div>
                                
                                <div>
                                    <label class="block text-[11px] font-black text-slate-500 uppercase tracking-wider mb-2">Cidade</label>
                                    <input 
                                        v-model="form.cidade" 
                                        class="w-full bg-slate-50 border-transparent focus:bg-white focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 rounded-2xl px-5 py-3.5 text-sm font-bold text-slate-700 transition-all uppercase placeholder:text-slate-300" 
                                        placeholder="LUANDA"
                                    />
                                </div>

                                <div>
                                    <label class="block text-[11px] font-black text-slate-500 uppercase tracking-wider mb-2">Rua / Bairro</label>
                                    <input 
                                        v-model="form.rua" 
                                        class="w-full bg-slate-50 border-transparent focus:bg-white focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 rounded-2xl px-5 py-3.5 text-sm font-bold text-slate-700 transition-all uppercase placeholder:text-slate-300" 
                                        placeholder="BAIRRO / ENDEREÇO"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Seguradora (Paciente) -->
                        <div v-if="tipoSeleccionado === 'paciente'" class="space-y-5">
                            <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] border-b border-slate-100 pb-3">
                                Seguro de Saúde
                            </h3>
                            <div>
                                <label class="block text-[11px] font-black text-slate-500 uppercase tracking-wider mb-2">Seguradora (Opcional)</label>
                                <input 
                                    v-model="form.seguradora" 
                                    class="w-full bg-slate-50 border-transparent focus:bg-white focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 rounded-2xl px-5 py-3.5 text-sm font-bold text-slate-700 transition-all uppercase placeholder:text-slate-300" 
                                    placeholder="NOME DA SEGURADORA"
                                />
                            </div>
                        </div>

                        <!-- Botões -->
                        <div class="flex justify-end gap-4 pt-4 border-t border-slate-100">
                            <button 
                                type="button"
                                @click="form.reset()" 
                                class="px-8 py-3.5 bg-slate-100 text-slate-500 rounded-2xl font-black text-[11px] tracking-widest hover:bg-slate-200 transition-all uppercase"
                            >
                                Limpar
                            </button>
                            <button 
                                type="submit" 
                                :disabled="form.processing" 
                                class="flex items-center px-10 py-3.5 bg-blue-600 text-white rounded-2xl font-black text-[11px] tracking-widest hover:bg-blue-700 transition-all shadow-xl shadow-blue-200 disabled:opacity-50 uppercase"
                            >
                                <span v-if="form.processing" class="w-4 h-4 mr-2 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                                <Save v-else class="w-4 h-4 mr-2" />
                                Registar {{ tiposEntidade.find(t => t.value === tipoSeleccionado)?.label }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>
