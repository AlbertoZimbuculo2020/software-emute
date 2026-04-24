<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { 
    Building2, 
    KeyRound, 
    Users, 
    Settings, 
    UserSquare2, 
    ChevronDown, 
    Hospital, 
    Boxes, 
    ChevronLeft, 
    ChevronRight,
    LogOut,
    UserCircle,
    UserRound,
    HeartPulse,
    Stethoscope,
    ShieldCheck,
    ConciergeBell,
    ClipboardList,
    MonitorSmartphone,
    Beaker,
    ShieldAlert,
    Database,
    FileText,
    Microscope,
    CalendarDays,
    Briefcase,
    BedDouble,
    ScanLine,
    Warehouse,
    ArrowDownToLine,
    ArrowUpFromLine,
    FileStack,
    BarChart3,
    Package,
    CheckCircle2,
    AlertCircle,
    X
} from 'lucide-vue-next';

const showingSidebar = ref(true);
const openMenus = ref({
    config: false,
    entidades: false,
    outros: false,
    hospitalar: false,
    stock: false
});

const user = usePage().props.auth.user;
const flash = computed(() => usePage().props.flash);
const showToast = ref(false);
const toastMessage = ref('');
const toastType = ref('success');

watch(() => flash.value, (newFlash) => {
    if (newFlash && newFlash.message) {
        toastMessage.value = newFlash.message;
        toastType.value = 'success';
        showToast.value = true;
        setTimeout(() => { showToast.value = false; }, 5000);
    }
    if (newFlash && newFlash.error) {
        toastMessage.value = newFlash.error;
        toastType.value = 'error';
        showToast.value = true;
        setTimeout(() => { showToast.value = false; }, 5000);
    }
}, { deep: true, immediate: true });

const toggleMenu = (menu) => {
    // Fecha outros menus ao abrir um novo (opcional, mas limpo)
    Object.keys(openMenus.value).forEach(key => {
        if (key !== menu) openMenus.value[key] = false;
    });
    openMenus.value[menu] = !openMenus.value[menu];
};
</script>

<template>
    <div class="min-h-screen bg-[#F4F7FA] flex font-sans selection:bg-blue-500 selection:text-white">
        
        <!-- Elegant Toast Notification -->
        <Transition name="toast">
            <div v-if="showToast" :class="toastType === 'success' ? 'bg-white border-emerald-500' : 'bg-white border-rose-500'" class="fixed top-8 right-8 z-[9999] p-6 rounded-[2rem] shadow-2xl shadow-slate-200 border-l-[12px] flex items-center gap-6 min-w-[400px]">
                <div :class="toastType === 'success' ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600'" class="w-14 h-14 rounded-3xl flex items-center justify-center shrink-0">
                    <CheckCircle2 v-if="toastType === 'success'" class="w-7 h-7" />
                    <AlertCircle v-else class="w-7 h-7" />
                </div>
                <div class="flex-grow">
                    <p class="text-[10px] font-black uppercase tracking-[0.2em] opacity-40 mb-1">{{ toastType === 'success' ? 'Sucesso' : 'Erro de Sistema' }}</p>
                    <p class="text-sm font-black text-slate-800 tracking-tight leading-snug">{{ toastMessage }}</p>
                </div>
                <button @click="showToast = false" class="p-2 hover:bg-slate-50 rounded-xl transition-all">
                    <X class="w-5 h-5 text-slate-300" />
                </button>
            </div>
        </Transition>
        
        <!-- Sidebar Moderna -->
        <aside 
            :class="showingSidebar ? 'w-64' : 'w-20'" 
            class="bg-[#2D82E3] text-white flex flex-col transition-all duration-300 shadow-2xl z-30 fixed inset-y-0"
        >
            <!-- Logo area -->
            <div class="h-16 flex items-center px-4 bg-blue-700/30 border-b border-white/10 overflow-hidden">
                <div class="min-w-[40px] h-10 flex items-center justify-center mr-3">
                    <img src="/images/logo.png" class="h-8 w-auto object-contain" alt="Logo" />
                </div>
                <span v-if="showingSidebar" class="font-black text-lg tracking-tighter uppercase truncate">EMUTE</span>
            </div>

            <!-- Menu Scrollable -->
            <div class="flex-grow overflow-y-auto py-4 custom-scrollbar">
                <nav class="px-3 space-y-3">
                    
                    <!-- Configurações Dropdown -->
                    <div class="space-y-1">
                        <button @click="toggleMenu('config')" class="w-full flex items-center px-3 py-2 text-xs font-bold rounded-lg hover:bg-white/10 transition-colors">
                            <Settings class="w-4 h-4 mr-3 opacity-80" />
                            <span v-if="showingSidebar" class="flex-grow text-left uppercase">Configurações</span>
                            <ChevronDown v-if="showingSidebar" :class="openMenus.config ? 'rotate-180' : ''" class="w-3 h-3 transition-transform" />
                        </button>
                        <div v-show="openMenus.config && showingSidebar" class="pl-10 space-y-1 animate-fadeIn">
                            <Link href="#" class="flex items-center py-1.5 text-[11px] opacity-80 hover:opacity-100">
                                <Building2 class="w-3 h-3 mr-2" /> Alterar dados da Empresa
                            </Link>
                            <Link href="#" class="flex items-center py-1.5 text-[11px] opacity-80 hover:opacity-100">
                                <KeyRound class="w-3 h-3 mr-2" /> Alterar Minha Senha
                            </Link>
                            <Link href="#" class="flex items-center py-1.5 text-[11px] opacity-80 hover:opacity-100">
                                <Users class="w-3 h-3 mr-2" /> Utilizadores
                            </Link>
                            <Link href="#" class="flex items-center py-1.5 text-[11px] opacity-80 hover:opacity-100">
                                <Settings class="w-3 h-3 mr-2" /> Definições
                            </Link>
                            <Link href="#" class="flex items-center py-1.5 text-[11px] opacity-80 hover:opacity-100">
                                <ShieldAlert class="w-3 h-3 mr-2" /> Permissões
                            </Link>
                            <Link href="#" class="flex items-center py-1.5 text-[11px] opacity-80 hover:opacity-100">
                                <Database class="w-3 h-3 mr-2" /> Cópia de Segurança
                            </Link>
                        </div>
                    </div>

                    <!-- Entidades Dropdown -->
                    <div class="space-y-1">
                        <button @click="toggleMenu('entidades')" class="w-full flex items-center px-3 py-2 text-xs font-bold rounded-lg hover:bg-white/10 transition-colors">
                            <UserSquare2 class="w-4 h-4 mr-3 opacity-80" />
                            <span v-if="showingSidebar" class="flex-grow text-left uppercase">Entidades</span>
                            <ChevronDown v-if="showingSidebar" :class="openMenus.entidades ? 'rotate-180' : ''" class="w-3 h-3 transition-transform" />
                        </button>
                        <div v-show="openMenus.entidades && showingSidebar" class="pl-10 space-y-1 animate-fadeIn">
                            <Link :href="route('clientes.index')" class="flex items-center py-1.5 text-[11px] opacity-80 hover:opacity-100">
                                <UserRound class="w-3 h-3 mr-2" /> Clientes
                            </Link>
                            <Link :href="route('pacientes.index')" class="flex items-center py-1.5 text-[11px] opacity-80 hover:opacity-100">
                                <HeartPulse class="w-3 h-3 mr-2" /> Paciente
                            </Link>
                            <Link :href="route('medicos.index')" class="flex items-center py-1.5 text-[11px] opacity-80 hover:opacity-100">
                                <Stethoscope class="w-3 h-3 mr-2" /> Médico
                            </Link>
                             <Link :href="route('hospitalar.seguradoras')" class="flex items-center py-1.5 text-[11px] opacity-80 hover:opacity-100">
                                <ShieldCheck class="w-3 h-3 mr-2" /> Seguradora
                            </Link>
                        </div>
                    </div>

                    <!-- Outros Cadastros Dropdown -->
                    <div class="space-y-1">
                        <button @click="toggleMenu('outros')" class="w-full flex items-center px-3 py-2 text-xs font-bold rounded-lg hover:bg-white/10 transition-colors">
                            <FileText class="w-4 h-4 mr-3 opacity-80" />
                            <span v-if="showingSidebar" class="flex-grow text-left uppercase">Outros Cadastros</span>
                            <ChevronDown v-if="showingSidebar" :class="openMenus.outros ? 'rotate-180' : ''" class="w-3 h-3 transition-transform" />
                        </button>
                        <div v-show="openMenus.outros && showingSidebar" class="pl-10 space-y-1 animate-fadeIn">
                            <Link :href="route('hospitalar.exames')" class="flex items-center py-1.5 text-[11px] opacity-80 hover:opacity-100">
                                <Microscope class="w-3 h-3 mr-2" /> Exames
                            </Link>
                            <Link :href="route('consultas.index')" class="flex items-center py-1.5 text-[11px] opacity-80 hover:opacity-100">
                                <CalendarDays class="w-3 h-3 mr-2" /> Consultas Médicas
                            </Link>
                            <Link :href="route('hospitalar.servicos')" class="flex items-center py-1.5 text-[11px] opacity-80 hover:opacity-100">
                                <Briefcase class="w-3 h-3 mr-2" /> Serviços
                            </Link>
                        </div>
                    </div>

                    <!-- Hospitalar Dropdown -->
                    <div class="space-y-1">
                        <button @click="toggleMenu('hospitalar')" class="w-full flex items-center px-3 py-2 text-xs font-bold rounded-lg hover:bg-white/10 transition-colors">
                            <Hospital class="w-4 h-4 mr-3 opacity-80" />
                            <span v-if="showingSidebar" class="flex-grow text-left uppercase">Hospitalar</span>
                            <ChevronDown v-if="showingSidebar" :class="openMenus.hospitalar ? 'rotate-180' : ''" class="w-3 h-3 transition-transform" />
                        </button>
                        <div v-show="openMenus.hospitalar && showingSidebar" class="pl-10 space-y-1 animate-fadeIn">
                            <Link :href="route('hospitalar.recepcao')" class="flex items-center py-1.5 text-[11px] opacity-80 hover:opacity-100">
                                <ConciergeBell class="w-3 h-3 mr-2" /> RECEPÇÃO
                            </Link>
                            <Link :href="route('hospitalar.triagem')" class="flex items-center py-1.5 text-[11px] opacity-80 hover:opacity-100">
                                <ClipboardList class="w-3 h-3 mr-2" /> TRIAGEM
                            </Link>
                             <Link :href="route('hospitalar.enfermaria.index')" class="flex items-center py-1.5 text-[11px] opacity-80 hover:opacity-100">
                                 <Stethoscope class="w-3 h-3 mr-2" /> ENFERMARIA
                             </Link>
                             <Link :href="route('hospitalar.internamento.index')" class="flex items-center py-1.5 text-[11px] opacity-80 hover:opacity-100">
                                 <BedDouble class="w-3 h-3 mr-2" /> INTERNAMENTO
                             </Link>
                            <Link :href="route('hospitalar.consultorio')" class="flex items-center py-1.5 text-[11px] opacity-80 hover:opacity-100">
                                <MonitorSmartphone class="w-3 h-3 mr-2" /> CONSULTÓRIO
                            </Link>
                             <Link :href="route('hospitalar.laboratorio.index')" class="flex items-center py-1.5 text-[11px] opacity-80 hover:opacity-100">
                                 <Beaker class="w-3 h-3 mr-2" /> LABORATÓRIO
                             </Link>
                            <Link :href="route('hospitalar.seguradoras')" class="flex items-center py-1.5 text-[11px] opacity-80 hover:opacity-100">
                                <ShieldCheck class="w-3 h-3 mr-2" /> SEGURADORAS
                            </Link>
                             <Link :href="route('hospitalar.raiox.index')" class="flex items-center py-1.5 text-[11px] opacity-80 hover:opacity-100">
                                 <ScanLine class="w-3 h-3 mr-2" /> RAIO X
                             </Link>
                        </div>
                    </div>

                    <!-- Gestão de Stock Dropdown -->
                    <div class="space-y-1">
                        <button @click="toggleMenu('stock')" class="w-full flex items-center px-3 py-2 text-xs font-bold rounded-lg hover:bg-white/10 transition-colors">
                            <Boxes class="w-4 h-4 mr-3 opacity-80" />
                            <span v-if="showingSidebar" class="flex-grow text-left uppercase">Gestão de Stock</span>
                            <ChevronDown v-if="showingSidebar" :class="openMenus.stock ? 'rotate-180' : ''" class="w-3 h-3 transition-transform" />
                        </button>
                        <div v-show="openMenus.stock && showingSidebar" class="pl-10 space-y-1 animate-fadeIn">
                            <Link href="#" class="flex items-center py-1.5 text-[11px] opacity-80 hover:opacity-100">
                                <Package class="w-3 h-3 mr-2" /> PRODUTOS
                            </Link>
                            <Link href="#" class="flex items-center py-1.5 text-[11px] opacity-80 hover:opacity-100">
                                <Warehouse class="w-3 h-3 mr-2" /> DEPÓSITOS
                            </Link>
                            <Link href="#" class="flex items-center py-1.5 text-[11px] opacity-80 hover:opacity-100">
                                <ArrowDownToLine class="w-3 h-3 mr-2" /> ENTRADA DE STOCK
                            </Link>
                            <Link href="#" class="flex items-center py-1.5 text-[11px] opacity-80 hover:opacity-100">
                                <ArrowUpFromLine class="w-3 h-3 mr-2" /> BAIXA DE STOCK
                            </Link>
                            <Link href="#" class="flex items-center py-1.5 text-[11px] opacity-80 hover:opacity-100">
                                <FileStack class="w-3 h-3 mr-2" /> DOCUMENTOS EMITIDOS
                            </Link>
                            <Link href="#" class="flex items-center py-1.5 text-[11px] opacity-80 hover:opacity-100">
                                <BarChart3 class="w-3 h-3 mr-2" /> RELATÓRIO E ESTATÍSTICA
                            </Link>
                        </div>
                    </div>
                </nav>
            </div>

            <!-- Footer Sidebar -->
            <div class="p-4 bg-blue-900/20 text-center">
                 <button @click="showingSidebar = !showingSidebar" class="p-2 hover:bg-white/10 rounded-full transition-all">
                    <ChevronLeft v-if="showingSidebar" class="w-5 h-5" />
                    <ChevronRight v-else class="w-5 h-5" />
                 </button>
            </div>
        </aside>

        <!-- Main Content Wrapper -->
        <main 
            :class="showingSidebar ? 'pl-64' : 'pl-20'" 
            class="flex-grow flex flex-col transition-all duration-300"
        >
            
            <!-- Barra Superior Principal -->
            <header class="h-16 bg-white/80 backdrop-blur-md border-b border-gray-100 flex items-center justify-between px-6 sticky top-0 z-20 shadow-sm">
                <!-- Esquerda: Info e Links -->
                <div class="flex items-center space-x-6 text-xs font-medium text-gray-500">
                    <div class="flex items-center space-x-2 bg-blue-50 text-blue-600 px-3 py-1.5 rounded-full border border-blue-100 shadow-sm">
                        <span class="relative flex h-2.5 w-2.5">
                          <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                          <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-blue-500"></span>
                        </span>
                        <span class="font-bold tracking-wide uppercase text-[10px]">Online</span>
                    </div>
                    <span class="hidden sm:inline bg-gray-100 px-2 py-1 rounded-md text-[10px] font-bold text-gray-600 tracking-wider">v1.0.0</span>
                    <div class="hidden lg:flex items-center space-x-4 border-l border-gray-200 pl-4">
                        <Link href="#" class="hover:text-blue-600 transition-colors flex items-center"><Settings class="w-3.5 h-3.5 mr-1.5"/> Servidor</Link>
                        <Link href="#" class="hover:text-blue-600 transition-colors flex items-center"><ArrowUpFromLine class="w-3.5 h-3.5 mr-1.5"/> Atualizações</Link>
                    </div>
                </div>

                <!-- Direita: Perfil e Ações -->
                <div class="flex items-center space-x-4">
                    <div class="hidden md:flex space-x-4 mr-2 text-xs font-medium text-gray-500">
                        <Link href="#" class="hover:text-blue-600 transition-colors flex items-center"><MonitorSmartphone class="w-3.5 h-3.5 mr-1.5"/> Suporte</Link>
                    </div>
                    
                    <!-- User Dropdown/Pill -->
                    <div class="flex items-center bg-white border border-gray-200 rounded-full p-1 pr-4 shadow-sm hover:shadow-md transition-all cursor-pointer group">
                        <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-blue-600 to-blue-400 flex items-center justify-center text-white shadow-inner mr-3 group-hover:scale-105 transition-transform">
                            <span class="text-xs font-bold">{{ (user.NOME_UTILIZADOR || user.name || 'E').charAt(0).toUpperCase() }}</span>
                        </div>
                        <div class="flex flex-col justify-center">
                            <span class="text-xs font-bold text-gray-700 leading-tight">{{ user.NOME_UTILIZADOR || user.name || 'EMUTE' }}</span>
                            <span class="text-[10px] text-gray-400 font-medium leading-tight">Administrador</span>
                        </div>
                        <div class="ml-4 pl-4 border-l border-gray-100 flex items-center">
                            <Link :href="route('logout')" method="post" as="button" class="text-gray-400 hover:text-red-500 transition-colors flex items-center justify-center w-6 h-6 rounded-full hover:bg-red-50" title="Terminar Sessão">
                                <LogOut class="w-4 h-4" />
                            </Link>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Breadcrumbs / Dashboard Tabs -->
            <div class="bg-white px-6 py-2 border-b border-gray-100 flex items-center space-x-2 shadow-sm">
                <div class="bg-gray-50 px-4 py-1.5 rounded-lg border border-gray-200 flex items-center space-x-2">
                    <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                    <span class="text-[10px] font-black uppercase tracking-widest text-gray-600">Dashboard</span>
                    <button class="text-gray-400 hover:text-red-500">×</button>
                </div>
            </div>

            <!-- Page Slots -->
            <div class="p-6 overflow-y-auto">
                <slot />
            </div>

        </main>
    </div>
</template>

<style>
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.2);
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-5px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fadeIn {
    animation: fadeIn 0.2s ease-out forwards;
}
</style>
