<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { 
    Users, 
    Search, 
    Plus, 
    Edit, 
    Trash2, 
    KeyRound,
    UserCircle2,
    ShieldCheck,
    ToggleRight,
    Stethoscope,
    X,
    ChevronDown,
    Building2,
    Lock,
    Eye,
    EyeOff,
    CheckCircle2,
    AlertCircle
} from 'lucide-vue-next';

const props = defineProps({
    utilizadores: {
        type: Array,
        default: () => []
    },
    perfis: {
        type: Array,
        default: () => []
    },
    medicos: {
        type: Array,
        default: () => []
    }
});

const searchQuery = ref('');
const isModalOpen = ref(false);
const isDeleteModalOpen = ref(false);
const itemToDelete = ref(null);
const editingId = ref(null);
const selectedUsers = ref([]);
const showPassword = ref(false);

const form = useForm({
    NOME_UTILIZADOR: '',
    SENHA: '',
    CONFIRMAR_SENHA: '',
    ID_PERFIL: '',
    ESTADO: 'Ativo',
    ACESSO: 'NAO',
    ID_PESSOA: '' // Linked to the doctor's Codigo
});

const filteredUsers = computed(() => {
    if (!searchQuery.value) return props.utilizadores;
    return props.utilizadores.filter(u => 
        u.NOME_UTILIZADOR.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
        (u.PERFIL_DESC && u.PERFIL_DESC.toLowerCase().includes(searchQuery.value.toLowerCase()))
    );
});

const toggleSelection = (id) => {
    const index = selectedUsers.value.indexOf(id);
    if (index > -1) {
        selectedUsers.value.splice(index, 1);
    } else {
        selectedUsers.value.push(id);
    }
};

const openCreateModal = () => {
    editingId.value = null;
    form.reset();
    form.clearErrors();
    isModalOpen.value = true;
};

const openEditModal = () => {
    if (selectedUsers.value.length !== 1) return;
    
    const id = selectedUsers.value[0];
    const user = props.utilizadores.find(u => u.ID_UTILIZADOR === id);
    
    if (user) {
        form.NOME_UTILIZADOR = user.NOME_UTILIZADOR;
        form.ID_PERFIL = user.ID_PERFIL;
        form.ESTADO = user.ESTADO;
        form.ACESSO = user.ACESSO || 'NAO';
        form.ID_PESSOA = user.ID_PESSOA || '';
        form.SENHA = '';
        form.CONFIRMAR_SENHA = '';
        
        editingId.value = id;
        isModalOpen.value = true;
    }
};

const confirmDelete = () => {
    if (selectedUsers.value.length !== 1) return;
    itemToDelete.value = selectedUsers.value[0];
    isDeleteModalOpen.value = true;
};

const executeDelete = () => {
    if (itemToDelete.value) {
        form.delete(route('configuracoes.utilizadores.destroy', itemToDelete.value), {
            onSuccess: () => {
                selectedUsers.value = [];
                isDeleteModalOpen.value = false;
                itemToDelete.value = null;
            }
        });
    }
};

const submit = () => {
    if (form.SENHA !== form.CONFIRMAR_SENHA) {
        form.setError('CONFIRMAR_SENHA', 'As senhas não coincidem');
        return;
    }

    if (editingId.value) {
        form.put(route('configuracoes.utilizadores.update', editingId.value), {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                isModalOpen.value = false;
                form.reset();
                editingId.value = null;
                selectedUsers.value = [];
            }
        });
    } else {
        form.post(route('configuracoes.utilizadores.store'), {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                isModalOpen.value = false;
                form.reset();
                selectedUsers.value = [];
            }
        });
    }
};

const toggleAdmin = () => {
    form.ACESSO = form.ACESSO === 'SIM' ? 'NAO' : 'SIM';
};

</script>

<template>
    <Head title="Utilizadores" />

    <DashboardLayout>
        
        <!-- Header -->
        <div class="mb-6 bg-white rounded-2xl p-6 shadow-sm border border-gray-100 flex justify-between items-center bg-gradient-to-r from-white to-blue-50/30">
            <div>
                <h1 class="text-xl font-black text-gray-800 tracking-tight flex items-center">
                    <Users class="w-6 h-6 mr-3 text-blue-600" />
                    GESTÃO DE UTILIZADORES
                </h1>
                <p class="text-gray-500 text-xs mt-1 font-medium italic">Faça o controlo de acessos, perfis e credenciais de login do sistema.</p>
            </div>
            
            <div class="flex items-center space-x-3">
                <div class="relative">
                    <Search class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
                    <input v-model="searchQuery" placeholder="Pesquisar utilizador..." class="pl-9 pr-4 py-2 border-gray-200 rounded-xl text-xs w-64 focus:ring-blue-500 focus:border-blue-500 bg-gray-50/50 hover:bg-white transition-all shadow-inner" />
                </div>
            </div>
        </div>

        <!-- Toolbar -->
        <div class="bg-white rounded-t-2xl border border-gray-100 p-3 shadow-sm flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <button @click="openEditModal" :disabled="selectedUsers.length !== 1" 
                        class="flex items-center px-4 py-2 text-xs font-bold rounded-xl transition-all border border-transparent"
                        :class="selectedUsers.length === 1 ? 'text-blue-700 hover:bg-blue-50 hover:border-blue-100' : 'text-gray-300 cursor-not-allowed'">
                    <Edit class="w-4 h-4 mr-2" /> Editar
                </button>
                <button @click="confirmDelete" :disabled="selectedUsers.length !== 1"
                        class="flex items-center px-4 py-2 text-xs font-bold rounded-xl transition-all border border-transparent"
                        :class="selectedUsers.length === 1 ? 'text-red-700 hover:bg-red-50 hover:border-red-100' : 'text-gray-300 cursor-not-allowed'">
                    <Trash2 class="w-4 h-4 mr-2" /> Eliminar
                </button>
                <div class="w-px h-6 bg-gray-200 mx-2"></div>
                <button @click="openCreateModal" class="flex items-center px-6 py-2 text-xs font-black text-white bg-blue-600 hover:bg-blue-700 rounded-xl transition-all shadow-lg shadow-blue-500/30 hover:scale-[1.02] active:scale-[0.98]">
                    <Plus class="w-4 h-4 mr-2" /> Novo Utilizador
                </button>
            </div>
        </div>

        <!-- Data Grid -->
        <div class="bg-white rounded-b-2xl border-x border-b border-gray-100 shadow-sm overflow-hidden flex flex-col mb-6">
            <div class="bg-gray-50/80 px-4 py-2 border-b border-gray-100 text-[10px] text-gray-400 italic font-black uppercase tracking-widest flex items-center">
                <ToggleRight class="w-3 h-3 mr-2 opacity-50" />
                LISTAGEM DE CONTAS ATIVAS NO SISTEMA
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse whitespace-nowrap">
                    <thead class="bg-gray-50/50">
                        <tr class="text-[10px] font-black text-gray-500 uppercase tracking-widest border-b border-gray-100">
                            <th class="px-6 py-4 w-10 text-center">
                                <span class="w-4 h-4 border-2 border-gray-200 rounded block mx-auto"></span>
                            </th>
                            <th class="px-6 py-4">Código</th>
                            <th class="px-6 py-4">Nome de Utilizador</th>
                            <th class="px-6 py-4">Perfil</th>
                            <th class="px-6 py-4 text-center">Admin</th>
                            <th class="px-6 py-4 text-center">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="u in filteredUsers" :key="u.ID_UTILIZADOR" 
                            @click="toggleSelection(u.ID_UTILIZADOR)"
                            class="group cursor-pointer transition-all hover:bg-blue-50/30"
                            :class="selectedUsers.includes(u.ID_UTILIZADOR) ? 'bg-blue-50/60' : ''">
                            <td class="px-6 py-4 text-center">
                                <div class="w-5 h-5 rounded-md border-2 transition-all flex items-center justify-center"
                                     :class="selectedUsers.includes(u.ID_UTILIZADOR) ? 'bg-blue-600 border-blue-600 shadow-md shadow-blue-200' : 'bg-white border-gray-200 group-hover:border-blue-300'">
                                    <CheckCircle2 v-if="selectedUsers.includes(u.ID_UTILIZADOR)" class="w-3 h-3 text-white" />
                                </div>
                            </td>
                            <td class="px-6 py-4 text-xs font-black text-gray-400">#{{ u.ID_UTILIZADOR }}</td>
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center mr-3 text-slate-500 group-hover:bg-blue-100 group-hover:text-blue-600 transition-colors">
                                        <UserCircle2 class="w-5 h-5" />
                                    </div>
                                    <span class="text-xs font-black text-gray-700 uppercase tracking-tight">{{ u.NOME_UTILIZADOR }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 bg-gray-100 text-gray-600 rounded-full text-[9px] font-black uppercase tracking-widest border border-gray-200 group-hover:bg-white transition-colors">
                                    {{ u.PERFIL_DESC || 'SEM PERFIL' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <ShieldCheck v-if="u.ACESSO === 'SIM'" class="w-5 h-5 text-emerald-500 mx-auto drop-shadow-sm" />
                                <Lock v-else class="w-4 h-4 text-gray-300 mx-auto" />
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span v-if="u.ESTADO === 'Activado' || u.ESTADO === 'Ativo'" class="inline-flex items-center px-2.5 py-0.5 rounded-md text-[9px] font-black bg-emerald-50 text-emerald-700 border border-emerald-100 uppercase tracking-widest">
                                    <span class="w-1 h-1 rounded-full bg-emerald-500 mr-1.5 animate-pulse"></span>
                                    ATIVO
                                </span>
                                <span v-else class="inline-flex items-center px-2.5 py-0.5 rounded-md text-[9px] font-black bg-red-50 text-red-700 border border-red-100 uppercase tracking-widest">
                                    INATIVO
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Create/Edit Modal -->
        <Transition name="modal">
            <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-md" @click="isModalOpen = false"></div>
                
                <div class="bg-white rounded-[2.5rem] shadow-2xl w-full max-w-2xl overflow-hidden relative animate-modalIn flex flex-col max-h-[90vh]">
                    <!-- Modal Header -->
                    <div class="p-8 pb-4 flex justify-between items-start">
                        <div class="flex items-center">
                            <div class="w-14 h-14 rounded-3xl bg-blue-600 flex items-center justify-center text-white shadow-xl shadow-blue-200 mr-5">
                                <Plus v-if="!editingId" class="w-7 h-7" />
                                <Edit v-else class="w-7 h-7" />
                            </div>
                            <div>
                                <h3 class="text-xl font-black text-slate-800 tracking-tight uppercase leading-none">{{ editingId ? 'Editar Conta' : 'Nova Conta' }}</h3>
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mt-2 italic">Defina as credenciais e permissões.</p>
                            </div>
                        </div>
                        <button @click="isModalOpen = false" class="p-3 bg-slate-50 text-slate-400 hover:bg-red-50 hover:text-red-500 rounded-2xl transition-all">
                            <X class="w-6 h-6" />
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div class="p-8 pt-4 overflow-y-auto custom-scrollbar flex-grow">
                        <form @submit.prevent="submit" class="space-y-8">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                <!-- Coluna Esq -->
                                <div class="space-y-6">
                                    <div class="space-y-1">
                                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Utilizador</label>
                                        <div class="relative group">
                                            <UserCircle2 class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-300 group-focus-within:text-blue-500 transition-colors" />
                                            <input v-model="form.NOME_UTILIZADOR" required placeholder="Ex: alberto.zimbuculo" 
                                                   class="w-full pl-12 pr-4 py-4 bg-slate-50 border-2 border-transparent focus:border-blue-500 focus:bg-white rounded-3xl text-sm font-bold text-slate-700 transition-all outline-none shadow-inner" />
                                        </div>
                                        <p v-if="form.errors.NOME_UTILIZADOR" class="text-[10px] font-bold text-red-500 ml-1 mt-1">{{ form.errors.NOME_UTILIZADOR }}</p>
                                    </div>

                                    <div class="space-y-1">
                                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Perfil de Acesso</label>
                                        <div class="relative">
                                            <ShieldCheck class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-300" />
                                            <select v-model="form.ID_PERFIL" required 
                                                    class="w-full pl-12 pr-10 py-4 bg-slate-50 border-2 border-transparent focus:border-blue-500 focus:bg-white rounded-3xl text-sm font-bold text-slate-700 transition-all outline-none appearance-none shadow-inner">
                                                <option value="" disabled>Escolha um perfil...</option>
                                                <option v-for="p in props.perfis" :key="p.ID" :value="p.ID">{{ p.PERFIL }}</option>
                                            </select>
                                            <ChevronDown class="absolute right-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" />
                                        </div>
                                    </div>

                                    <!-- Vínculo com Médico -->
                                    <div class="space-y-1">
                                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Profissional Médico (Vínculo)</label>
                                        <div class="relative">
                                            <Stethoscope class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-300" />
                                            <select v-model="form.ID_PESSOA" 
                                                    class="w-full pl-12 pr-10 py-4 bg-slate-50 border-2 border-transparent focus:border-blue-500 focus:bg-white rounded-3xl text-sm font-bold text-slate-700 transition-all outline-none appearance-none shadow-inner">
                                                <option value="">Nenhum (Utilizador Comum)</option>
                                                <option v-for="m in props.medicos" :key="m.Codigo" :value="m.Codigo">{{ m.Nome }}</option>
                                            </select>
                                            <ChevronDown class="absolute right-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" />
                                        </div>
                                        <p class="text-[9px] text-slate-400 mt-1 italic leading-tight">Necessário para que o médico veja apenas os seus pacientes no consultório.</p>
                                    </div>

                                    <!-- Switch Admin logic -->
                                    <div class="bg-blue-50/50 p-5 rounded-[2rem] border border-blue-100 flex items-center justify-between group cursor-pointer hover:bg-blue-50 transition-colors" @click="toggleAdmin">
                                        <div class="flex items-center">
                                            <div class="w-10 h-10 rounded-2xl flex items-center justify-center mr-4 transition-all"
                                                 :class="form.ACESSO === 'SIM' ? 'bg-blue-600 text-white shadow-lg shadow-blue-200' : 'bg-slate-200 text-slate-400'">
                                                <Lock class="w-4 h-4" />
                                            </div>
                                            <div>
                                                <p class="text-xs font-black text-slate-800 uppercase tracking-tight">Privilégios Administrativos</p>
                                                <p class="text-[9px] font-bold text-slate-400 uppercase">Concede controle total</p>
                                            </div>
                                        </div>
                                        <div class="w-12 h-6 rounded-full relative transition-all duration-300 shadow-inner"
                                             :class="form.ACESSO === 'SIM' ? 'bg-blue-600' : 'bg-slate-300'">
                                            <div class="absolute top-1 w-4 h-4 bg-white rounded-full transition-all duration-300 shadow-sm"
                                                 :class="form.ACESSO === 'SIM' ? 'left-7' : 'left-1'"></div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Coluna Dir (Passwords) -->
                                <div class="space-y-6">
                                    <div class="space-y-1">
                                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Senha ({{ editingId ? 'Opcional' : 'Obrigatório' }})</label>
                                        <div class="relative group">
                                            <KeyRound class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-300 group-focus-within:text-blue-500 transition-colors" />
                                            <input :type="showPassword ? 'text' : 'password'" v-model="form.SENHA" :required="!editingId" placeholder="••••••••" 
                                                   class="w-full pl-12 pr-12 py-4 bg-slate-50 border-2 border-transparent focus:border-blue-500 focus:bg-white rounded-3xl text-sm font-bold text-slate-700 transition-all outline-none shadow-inner" />
                                            <button type="button" @click="showPassword = !showPassword" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-300 hover:text-blue-500">
                                                <Eye v-if="!showPassword" class="w-4 h-4" />
                                                <EyeOff v-else class="w-4 h-4" />
                                            </button>
                                        </div>
                                    </div>

                                    <div class="space-y-1">
                                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Confirmar Senha</label>
                                        <div class="relative group">
                                            <KeyRound class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-300 group-focus-within:text-blue-500 transition-colors" />
                                            <input :type="showPassword ? 'text' : 'password'" v-model="form.CONFIRMAR_SENHA" :required="Boolean(form.SENHA)" placeholder="••••••••" 
                                                   class="w-full pl-12 pr-12 py-4 bg-slate-50 border-2 border-transparent focus:border-blue-500 focus:bg-white rounded-3xl text-sm font-bold text-slate-700 transition-all outline-none shadow-inner" />
                                        </div>
                                        <p v-if="form.errors.CONFIRMAR_SENHA" class="text-[10px] font-bold text-red-500 ml-1 mt-1">{{ form.errors.CONFIRMAR_SENHA }}</p>
                                    </div>

                                    <div class="space-y-1 py-1">
                                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Estado da Conta</label>
                                        <div class="flex space-x-2">
                                            <button type="button" @click="form.ESTADO = 'Ativo'" 
                                                    :class="form.ESTADO === 'Ativo' ? 'bg-emerald-600 text-white border-emerald-600 shadow-lg shadow-emerald-100' : 'bg-white text-slate-400 border-slate-100 hover:border-emerald-200'"
                                                    class="flex-1 py-3 border-2 rounded-2xl text-[10px] font-black uppercase tracking-widest transition-all">
                                                Ativo
                                            </button>
                                            <button type="button" @click="form.ESTADO = 'Inativo'" 
                                                    :class="form.ESTADO === 'Inativo' ? 'bg-red-600 text-white border-red-600 shadow-lg shadow-red-100' : 'bg-white text-slate-400 border-slate-100 hover:border-red-200'"
                                                    class="flex-1 py-3 border-2 rounded-2xl text-[10px] font-black uppercase tracking-widest transition-all">
                                                Inativo
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Modal Footer -->
                    <div class="p-8 pt-0 flex justify-end space-x-4">
                        <button @click="isModalOpen = false" 
                                class="px-8 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest hover:bg-slate-50 rounded-3xl transition-all">
                            Descartar
                        </button>
                        <button @click="submit" :disabled="form.processing"
                                class="px-10 py-4 text-[10px] font-black text-white bg-blue-600 hover:bg-blue-700 rounded-3xl shadow-2xl shadow-blue-200 transition-all flex items-center disabled:opacity-50">
                            <span v-if="form.processing" class="w-4 h-4 mr-3 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                            {{ editingId ? 'Guardar Alterações' : 'Criar Utilizador' }}
                        </button>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- Delete Confirmation -->
        <Transition name="modal">
            <div v-if="isDeleteModalOpen" class="fixed inset-0 z-[60] flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-xl" @click="isDeleteModalOpen = false"></div>
                <div class="bg-white rounded-[3rem] shadow-2xl p-10 w-full max-w-sm relative text-center animate-modalIn border-8 border-white">
                    <div class="w-24 h-24 bg-red-50 rounded-[2.5rem] flex items-center justify-center mx-auto mb-8 shadow-inner">
                        <Trash2 class="w-10 h-10 text-red-500" />
                    </div>
                    <h3 class="text-2xl font-black text-slate-800 tracking-tight mb-3">Eliminar Conta?</h3>
                    <p class="text-sm font-medium text-slate-400 leading-relaxed mb-10">Esta ação removerá permanentemente o acesso deste utilizador ao sistema.</p>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <button @click="isDeleteModalOpen = false" class="py-5 bg-slate-100 text-slate-400 text-[10px] font-black uppercase tracking-widest rounded-3xl hover:bg-slate-200 transition-all">
                            Cancelar
                        </button>
                        <button @click="executeDelete" :disabled="form.processing" class="py-5 bg-red-600 text-white text-[10px] font-black uppercase tracking-widest rounded-3xl hover:bg-red-700 shadow-xl shadow-red-200 transition-all flex items-center justify-center">
                             <span v-if="form.processing" class="w-3 h-3 mr-2 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                            Sim, Eliminar
                        </button>
                    </div>
                </div>
            </div>
        </Transition>

    </DashboardLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 10px;
}

.modal-enter-active, .modal-leave-active {
    transition: opacity 0.3s ease;
}
.modal-enter-from, .modal-leave-to {
    opacity: 0;
}

@keyframes modalIn {
    from { opacity: 0; transform: scale(0.9) translateY(20px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}
.animate-modalIn {
    animation: modalIn 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

/* Background grid effect */
.bg-grid-white {
    background-image: radial-gradient(circle, #fff 1px, transparent 1px);
}
</style>
