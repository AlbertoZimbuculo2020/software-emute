<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, watch, onMounted } from 'vue';
import { 
    ShieldCheck, 
    Save, 
    ChevronDown, 
    ChevronRight,
    Hospital,
    Boxes,
    ShoppingCart,
    CheckSquare,
    Square,
    Search,
    UserCircle2,
    Lock,
    Settings,
    LayoutDashboard
} from 'lucide-vue-next';
import axios from 'axios';

const props = defineProps({
    perfis: Array,
    availablePermissions: Object
});

const selectedProfile = ref(null);
const currentPermissions = ref([]);
const loading = ref(false);

const form = useForm({
    profileId: null,
    permissions: []
});

const loadPermissions = async (profileId) => {
    loading.ref = true;
    try {
        const response = await axios.get(route('configuracoes.permissoes.get', profileId));
        currentPermissions.value = response.data;
        
        // Initialize form permissions based on available ones and current values
        const newPerms = [];
        Object.keys(props.availablePermissions).forEach(module => {
            props.availablePermissions[module].forEach(p => {
                const existing = currentPermissions.value.find(cp => cp.NOME === p.nome);
                newPerms.push({
                    form: p.form,
                    nome: p.nome,
                    descricao: p.descricao,
                    module: module,
                    estado: existing ? existing.ESTADO === 'True' : false
                });
            });
        });
        form.permissions = newPerms;
        form.profileId = profileId;
    } catch (error) {
        console.error("Error loading permissions", error);
    } finally {
        loading.value = false;
    }
};

watch(selectedProfile, (newVal) => {
    if (newVal) {
        loadPermissions(newVal);
    }
});

const togglePermission = (nome) => {
    const perm = form.permissions.find(p => p.nome === nome);
    if (perm) {
        perm.estado = !perm.estado;
    }
};

const submit = () => {
    form.post(route('configuracoes.permissoes.update'), {
        preserveScroll: true,
        onSuccess: () => {
            // Success handled by flash message
        }
    });
};

onMounted(() => {
    if (props.perfis.length > 0) {
        selectedProfile.value = props.perfis[0].ID;
    }
});

</script>

<template>
    <Head title="Controle de Permissões" />

    <DashboardLayout>
        <!-- Header -->
        <div class="mb-8 bg-white rounded-3xl p-8 shadow-sm border border-gray-100 flex flex-col md:flex-row justify-between items-start md:items-center gap-6 bg-gradient-to-br from-white to-slate-50/50 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-64 h-64 bg-blue-500/5 rounded-full -mr-32 -mt-32 blur-3xl"></div>
            <div class="relative z-10">
                <div class="flex items-center space-x-3 mb-2">
                    <div class="w-10 h-10 rounded-2xl bg-blue-600 flex items-center justify-center text-white shadow-lg shadow-blue-200">
                        <ShieldCheck class="w-6 h-6" />
                    </div>
                    <h1 class="text-2xl font-black text-slate-800 tracking-tight uppercase">Configuração de Perfis</h1>
                </div>
                <p class="text-slate-400 text-xs font-bold uppercase tracking-widest ml-1">Defina quais módulos e funcionalidades cada perfil pode acessar.</p>
            </div>
            
            <div class="relative z-10 flex items-center bg-white border-2 border-slate-100 rounded-[2rem] p-1 shadow-inner group transition-all hover:border-blue-200 min-w-[300px]">
                <div class="pl-4 pr-3 text-slate-400">
                    <UserCircle2 class="w-5 h-5" />
                </div>
                <select v-model="selectedProfile" class="flex-grow bg-transparent border-none focus:ring-0 text-sm font-black text-slate-700 uppercase tracking-tight py-3 appearance-none">
                    <option v-for="p in props.perfis" :key="p.ID" :value="p.ID">{{ p.PERFIL }}</option>
                </select>
                <div class="pr-4 text-slate-300">
                    <ChevronDown class="w-4 h-4" />
                </div>
            </div>
        </div>

        <div v-if="selectedProfile" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Colunas de Permissões -->
            <div v-for="(modulePerms, moduleName) in props.availablePermissions" :key="moduleName" class="lg:col-span-1">
                <div class="bg-white rounded-[2.5rem] shadow-xl shadow-slate-100 border border-slate-100 overflow-hidden group transition-all duration-500 hover:shadow-2xl hover:shadow-blue-500/5 hover:-translate-y-1">
                    <!-- Module Header -->
                    <div class="p-8 border-b border-slate-50 flex items-center justify-between"
                         :class="moduleName === 'Hospitalar' ? 'bg-blue-600' : 'bg-slate-800'">
                        <div class="flex items-center space-x-4">
                            <div class="w-12 h-12 rounded-2xl bg-white/10 flex items-center justify-center text-white backdrop-blur-md">
                                <Hospital v-if="moduleName === 'Hospitalar'" class="w-6 h-6" />
                                <Boxes v-else-if="moduleName === 'Stock'" class="w-6 h-6" />
                                <ShoppingCart v-else class="w-6 h-6" />
                            </div>
                            <div>
                                <h3 class="text-lg font-black text-white tracking-tight uppercase leading-none">{{ moduleName === 'Hospitalar' ? 'Módulo Clínico' : 'Gestão de Inventário' }}</h3>
                                <p class="text-[9px] font-black text-white/50 uppercase tracking-[0.2em] mt-2 italic">Controle de acesso {{ moduleName }}</p>
                            </div>
                        </div>
                        <CheckSquare class="w-5 h-5 text-white/20" />
                    </div>

                    <!-- Permissions List -->
                    <div class="p-6 space-y-2 max-h-[600px] overflow-y-auto custom-scrollbar">
                        <div v-for="perm in form.permissions.filter(p => p.module === moduleName)" :key="perm.nome" 
                             @click="togglePermission(perm.nome)"
                             class="flex items-center justify-between p-4 rounded-3xl cursor-pointer transition-all border-2 group/item"
                             :class="perm.estado ? 'bg-emerald-50/50 border-emerald-100 hover:bg-emerald-50' : 'bg-slate-50/50 border-transparent hover:bg-slate-50'">
                            <div class="flex items-center space-x-4">
                                <div class="w-10 h-10 rounded-2xl flex items-center justify-center transition-all shadow-sm"
                                     :class="perm.estado ? 'bg-white text-emerald-600 border border-emerald-100' : 'bg-white text-slate-300 border border-slate-200'">
                                    <Settings v-if="perm.module === 'Config'" class="w-4 h-4" />
                                    <LayoutDashboard v-else class="w-4 h-4" />
                                </div>
                                <div>
                                    <p class="text-xs font-black tracking-tight" :class="perm.estado ? 'text-emerald-800' : 'text-slate-500 group-hover/item:text-slate-700'">{{ perm.descricao }}</p>
                                    <p class="text-[9px] font-bold opacity-40 uppercase tracking-widest mt-0.5">{{ perm.nome }}</p>
                                </div>
                            </div>
                            
                            <div class="w-10 h-10 rounded-2xl flex items-center justify-center transition-all duration-500"
                                 :class="perm.estado ? 'bg-emerald-600 text-white rotate-0' : 'bg-slate-200 text-slate-400 -rotate-90 opacity-40'">
                                <CheckSquare v-if="perm.estado" class="w-4 h-4" />
                                <Square v-else class="w-4 h-4" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Coluna de Ações Rápidas & Info -->
            <div class="lg:col-span-1 space-y-8">
                <!-- Salvar -->
                <div class="bg-blue-600 rounded-[2.5rem] p-10 text-white shadow-2xl shadow-blue-500/30 relative overflow-hidden group">
                    <div class="absolute top-0 right-0 w-48 h-48 bg-white/10 rounded-full -mr-20 -mt-20 blur-2xl group-hover:scale-150 transition-transform duration-700"></div>
                    <h3 class="text-xl font-black tracking-tight uppercase mb-2 relative z-10">Confirmar Alterações</h3>
                    <p class="text-blue-100 text-[11px] font-bold mb-8 leading-relaxed opacity-80 relative z-10">As novas definições de acesso entrarão em vigor no próximo login do utilizador.</p>
                    
                    <button @click="submit" :disabled="form.processing"
                            class="w-full py-5 bg-white text-blue-600 text-xs font-black uppercase tracking-widest rounded-3xl shadow-xl hover:shadow-white/20 transition-all active:scale-95 disabled:opacity-50 flex items-center justify-center relative z-10 group/btn">
                        <Save v-if="!form.processing" class="w-4 h-4 mr-3 group-hover/btn:rotate-12 transition-transform" />
                        <span v-else class="w-4 h-4 mr-3 border-2 border-blue-600/30 border-t-blue-600 rounded-full animate-spin"></span>
                        Gravar Configurações
                    </button>
                    
                    <div class="mt-8 flex items-center justify-center space-x-6 opacity-40 text-[9px] font-black uppercase tracking-[0.2em] relative z-10">
                        <div class="flex items-center"><div class="w-2 h-2 rounded-full bg-white mr-2"></div> Auto Backup</div>
                        <div class="flex items-center"><div class="w-2 h-2 rounded-full bg-white mr-2"></div> Sync Log</div>
                    </div>
                </div>

                <!-- Info Box -->
                <div class="bg-white rounded-[2.5rem] p-10 border border-slate-100 shadow-xl shadow-slate-100 relative group overflow-hidden">
                    <div class="absolute -bottom-10 -left-10 w-40 h-40 bg-slate-50 rounded-full blur-2xl group-hover:-translate-x-10 transition-transform duration-1000"></div>
                    <div class="flex items-center space-x-4 mb-6 relative z-10">
                        <div class="w-12 h-12 rounded-2xl bg-amber-50 flex items-center justify-center text-amber-500">
                            <Lock class="w-6 h-6" />
                        </div>
                        <h4 class="text-sm font-black text-slate-800 uppercase tracking-tight">Utilizador Admin</h4>
                    </div>
                    <p class="text-xs font-bold text-slate-400 leading-relaxed relative z-10">
                        Utilizadores marcados como <span class="text-blue-600 font-black">ADMIN</span> na página de utilizadores ignoram as regras deste perfil e possuem <span class="italic">acesso total</span> a todos os menus e módulos.
                    </p>
                    <div class="mt-8 pt-8 border-t border-slate-50 relative z-10">
                        <Link :href="route('configuracoes.utilizadores.index')" class="flex items-center text-[10px] font-black text-blue-600 uppercase tracking-widest hover:translate-x-2 transition-transform">
                            Ir para Utilizadores <ChevronRight class="w-3 h-3 ml-2" />
                        </Link>
                    </div>
                </div>
            </div>
        </div>
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
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #cbd5e1;
}

@keyframes slideIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-slideIn {
    animation: slideIn 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
</style>
