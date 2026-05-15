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
    X,
    Menu
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
const permissions = computed(() => usePage().props.auth.permissions || []);

const can = (permission) => {
    if (permissions.value.includes('*')) return true;
    return permissions.value.includes(permission);
};

// Seção visível se qualquer item dela for visível
const canSeeModule = (perms) => {
    if (permissions.value.includes('*')) return true;
    return perms.some(p => permissions.value.includes(p));
};

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

const clinicData = computed(() => usePage().props.clinicData);

const pageTitle = computed(() => {
    const component = usePage().component;
    if (!component) return 'Sistema';
    
    const parts = component.split('/');
    let name = parts[parts.length - 1];
    
    // Specific translations
    const titles = {
        'Laboratorio': 'Laboratório',
        'Consultorio': 'Consultório',
        'Internamento': 'Internamento',
        'Triagem': 'Triagem',
        'Enfermaria': 'Enfermaria',
        'RaioX': 'Raio X',
        'Recepcao': 'Recepção',
        'Seguradoras': 'Seguradoras',
        'Pacientes': 'Pacientes',
        'Medicos': 'Médicos',
        'Clientes': 'Clientes',
        'Utilizadores': 'Utilizadores',
        'Dashboard': 'Dashboard',
        'Empresa': 'Empresa',
        'Consultas': 'Consultas',
        'Exames': 'Exames',
        'Servicos': 'Serviços'
    };
    
    return titles[name] || name.replace(/([A-Z])/g, ' $1').trim();
});
</script>

<template>
    <div class="h-screen w-full bg-[#F4F7FA] flex font-sans selection:bg-blue-500 selection:text-white overflow-hidden">
        
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
        
        <!-- Sidebar Backdrop (Mobile) -->
        <div 
            v-if="showingSidebar" 
            @click="showingSidebar = false" 
            class="fixed inset-0 bg-black/50 z-40 lg:hidden"
        ></div>

        <!-- Sidebar Moderna -->
        <aside 
            :class="[
                showingSidebar ? 'translate-x-0 w-64' : '-translate-x-full lg:translate-x-0 lg:w-20',
                'bg-[#2D82E3] text-white flex flex-col transition-all duration-300 shadow-2xl z-50 fixed inset-y-0 lg:sticky lg:top-0 lg:h-screen'
            ]"
        >
            <!-- Logo area -->
            <div :class="showingSidebar ? 'justify-between px-4' : 'justify-center'" class="h-16 flex items-center bg-blue-700/30 border-b border-white/10 overflow-hidden transition-all">
                <Link v-if="showingSidebar" :href="route('dashboard')" class="flex items-center hover:opacity-80 transition-opacity">
                    <div class="min-w-[40px] h-10 flex items-center justify-center mr-3">
                        <img src="/images/logo.png" class="h-8 w-auto object-contain" alt="Logo" />
                    </div>
                    <span class="font-black text-lg tracking-tighter uppercase truncate">EMUTE</span>
                </Link>
                
                <!-- Toggle Button at Top -->
                <button @click="showingSidebar = !showingSidebar" class="p-2 hover:bg-white/10 rounded-lg transition-all hidden lg:flex items-center justify-center">
                    <Menu class="w-5 h-5" />
                </button>
            </div>

            <!-- Menu Scrollable -->
            <div class="flex-grow overflow-y-auto py-4 custom-scrollbar">
                <nav class="px-3 space-y-3">
                    
                    <!-- Configurações Dropdown -->
                    <div v-if="canSeeModule(['btnEmpresa', 'accordionUtilizadores', 'accordionDefinicoes', 'accordionPermissoes', 'accordionBackup'])" class="space-y-1">
                        <button @click="toggleMenu('config')" :class="showingSidebar ? 'px-3 justify-start' : 'px-0 justify-center'" class="w-full flex items-center py-2 text-xs font-bold rounded-lg hover:bg-white/10 transition-colors">
                            <Settings :class="showingSidebar ? 'mr-3' : 'mr-0'" class="w-4 h-4 opacity-80" />
                            <span v-if="showingSidebar" class="flex-grow text-left uppercase">Configurações</span>
                            <ChevronDown v-if="showingSidebar" :class="openMenus.config ? 'rotate-180' : ''" class="w-3 h-3 transition-transform" />
                        </button>
                        <div v-show="openMenus.config && showingSidebar" class="pl-10 space-y-1 animate-fadeIn">
                            <Link v-if="can('btnEmpresa')" :href="route('configuracoes.empresa.index')" class="flex items-center py-1.5 text-[11px] opacity-80 hover:opacity-100">
                                <Building2 class="w-3 h-3 mr-2" /> Alterar dados da Empresa
                            </Link>
                            <Link :href="route('configuracoes.senha.index')" class="flex items-center py-1.5 text-[11px] opacity-80 hover:opacity-100">
                                <KeyRound class="w-3 h-3 mr-2" /> Alterar Minha Senha
                            </Link>
                            <Link v-if="can('accordionUtilizadores')" :href="route('configuracoes.utilizadores.index')" class="flex items-center py-1.5 text-[11px] opacity-80 hover:opacity-100">
                                <Users class="w-3 h-3 mr-2" /> Utilizadores
                            </Link>
                            <Link v-if="can('accordionDefinicoes')" href="#" class="flex items-center py-1.5 text-[11px] opacity-80 hover:opacity-100">
                                <Settings class="w-3 h-3 mr-2" /> Definições
                            </Link>
                            <Link v-if="can('accordionPermissoes')" :href="route('configuracoes.permissoes.index')" class="flex items-center py-1.5 text-[11px] opacity-80 hover:opacity-100">
                                <ShieldAlert class="w-3 h-3 mr-2" /> Permissões
                            </Link>
                            <Link v-if="can('accordionBackup')" href="#" class="flex items-center py-1.5 text-[11px] opacity-80 hover:opacity-100">
                                <Database class="w-3 h-3 mr-2" /> Cópia de Segurança
                            </Link>
                        </div>
                    </div>

                    <!-- Entidades Dropdown -->
                    <div v-if="canSeeModule(['btnCliente', 'accordionPacientes', 'accordionMedicos', 'accordionSeguradora'])" class="space-y-1">
                        <button @click="toggleMenu('entidades')" :class="showingSidebar ? 'px-3 justify-start' : 'px-0 justify-center'" class="w-full flex items-center py-2 text-xs font-bold rounded-lg hover:bg-white/10 transition-colors">
                            <UserSquare2 :class="showingSidebar ? 'mr-3' : 'mr-0'" class="w-4 h-4 opacity-80" />
                            <span v-if="showingSidebar" class="flex-grow text-left uppercase">Entidades</span>
                            <ChevronDown v-if="showingSidebar" :class="openMenus.entidades ? 'rotate-180' : ''" class="w-3 h-3 transition-transform" />
                        </button>
                        <div v-show="openMenus.entidades && showingSidebar" class="pl-10 space-y-1 animate-fadeIn">
                            <Link v-if="can('btnCliente')" :href="route('clientes.index')" class="flex items-center py-1.5 text-[11px] opacity-80 hover:opacity-100">
                                <UserRound class="w-3 h-3 mr-2" /> Clientes
                            </Link>
                            <Link v-if="can('accordionPacientes')" :href="route('pacientes.index')" class="flex items-center py-1.5 text-[11px] opacity-80 hover:opacity-100">
                                <HeartPulse class="w-3 h-3 mr-2" /> Paciente
                            </Link>
                            <Link v-if="can('accordionMedicos')" :href="route('medicos.index')" class="flex items-center py-1.5 text-[11px] opacity-80 hover:opacity-100">
                                <Stethoscope class="w-3 h-3 mr-2" /> Médico
                            </Link>
                             <Link v-if="can('accordionSeguradora')" :href="route('hospitalar.seguradoras')" class="flex items-center py-1.5 text-[11px] opacity-80 hover:opacity-100">
                                <ShieldCheck class="w-3 h-3 mr-2" /> Seguradora
                            </Link>
                        </div>
                    </div>

                    <!-- Outros Cadastros Dropdown -->
                    <div v-if="canSeeModule(['accordionExames', 'accordionConsultas', 'accordionServicos'])" class="space-y-1">
                        <button @click="toggleMenu('outros')" :class="showingSidebar ? 'px-3 justify-start' : 'px-0 justify-center'" class="w-full flex items-center py-2 text-xs font-bold rounded-lg hover:bg-white/10 transition-colors">
                            <FileText :class="showingSidebar ? 'mr-3' : 'mr-0'" class="w-4 h-4 opacity-80" />
                            <span v-if="showingSidebar" class="flex-grow text-left uppercase">Outros Cadastros</span>
                            <ChevronDown v-if="showingSidebar" :class="openMenus.outros ? 'rotate-180' : ''" class="w-3 h-3 transition-transform" />
                        </button>
                        <div v-show="openMenus.outros && showingSidebar" class="pl-10 space-y-1 animate-fadeIn">
                            <Link v-if="can('accordionExames')" :href="route('hospitalar.exames')" class="flex items-center py-1.5 text-[11px] opacity-80 hover:opacity-100">
                                <Microscope class="w-3 h-3 mr-2" /> Exames
                            </Link>
                            <Link v-if="can('accordionConsultas')" :href="route('consultas.index')" class="flex items-center py-1.5 text-[11px] opacity-80 hover:opacity-100">
                                <CalendarDays class="w-3 h-3 mr-2" /> Consultas Médicas
                            </Link>
                            <Link v-if="can('accordionServicos')" :href="route('hospitalar.servicos')" class="flex items-center py-1.5 text-[11px] opacity-80 hover:opacity-100">
                                <Briefcase class="w-3 h-3 mr-2" /> Serviços
                            </Link>
                        </div>
                    </div>

                    <!-- Hospitalar Dropdown -->
                    <div v-if="canSeeModule(['btnRecepcao', 'accordionTriagem', 'accordionEnfermaria', 'accordionInternamento', 'accordionConsultorio', 'accordionLaboratorio', 'accordionRaioX'])" class="space-y-1">
                        <button @click="toggleMenu('hospitalar')" :class="showingSidebar ? 'px-3 justify-start' : 'px-0 justify-center'" class="w-full flex items-center py-2 text-xs font-bold rounded-lg hover:bg-white/10 transition-colors">
                            <Hospital :class="showingSidebar ? 'mr-3' : 'mr-0'" class="w-4 h-4 opacity-80" />
                            <span v-if="showingSidebar" class="flex-grow text-left uppercase">Hospitalar</span>
                            <ChevronDown v-if="showingSidebar" :class="openMenus.hospitalar ? 'rotate-180' : ''" class="w-3 h-3 transition-transform" />
                        </button>
                        <div v-show="openMenus.hospitalar && showingSidebar" class="pl-10 space-y-1 animate-fadeIn">
                            <Link v-if="can('btnRecepcao')" :href="route('hospitalar.recepcao')" class="flex items-center py-1.5 text-[11px] opacity-80 hover:opacity-100">
                                <ConciergeBell class="w-3 h-3 mr-2" /> RECEPÇÃO
                            </Link>
                            <Link v-if="can('accordionTriagem')" :href="route('hospitalar.triagem')" class="flex items-center py-1.5 text-[11px] opacity-80 hover:opacity-100">
                                <ClipboardList class="w-3 h-3 mr-2" /> TRIAGEM
                            </Link>
                             <Link v-if="can('accordionEnfermaria')" :href="route('hospitalar.enfermaria.index')" class="flex items-center py-1.5 text-[11px] opacity-80 hover:opacity-100">
                                 <Stethoscope class="w-3 h-3 mr-2" /> ENFERMARIA
                             </Link>
                             <Link v-if="can('accordionInternamento')" :href="route('hospitalar.internamento.index')" class="flex items-center py-1.5 text-[11px] opacity-80 hover:opacity-100">
                                 <BedDouble class="w-3 h-3 mr-2" /> INTERNAMENTO
                             </Link>
                            <Link v-if="can('accordionConsultorio')" :href="route('hospitalar.consultorio')" class="flex items-center py-1.5 text-[11px] opacity-80 hover:opacity-100">
                                <MonitorSmartphone class="w-3 h-3 mr-2" /> CONSULTÓRIO
                            </Link>
                             <Link v-if="can('accordionLaboratorio')" :href="route('hospitalar.laboratorio.index')" class="flex items-center py-1.5 text-[11px] opacity-80 hover:opacity-100">
                                 <Beaker class="w-3 h-3 mr-2" /> LABORATÓRIO
                             </Link>
                             <Link v-if="can('accordionRaioX')" :href="route('hospitalar.raiox.index')" class="flex items-center py-1.5 text-[11px] opacity-80 hover:opacity-100">
                                 <ScanLine class="w-3 h-3 mr-2" /> RAIO X
                             </Link>
                        </div>
                    </div>

                    <!-- Gestão de Stock Dropdown -->
                    <div v-if="canSeeModule(['accordionProdutos', 'accordionDepositos', 'accordionEntrada', 'accordionBaixa', 'accordionDocumentos', 'accordionRelatorios'])" class="space-y-1">
                        <button @click="toggleMenu('stock')" :class="showingSidebar ? 'px-3 justify-start' : 'px-0 justify-center'" class="w-full flex items-center py-2 text-xs font-bold rounded-lg hover:bg-white/10 transition-colors">
                            <Boxes :class="showingSidebar ? 'mr-3' : 'mr-0'" class="w-4 h-4 opacity-80" />
                            <span v-if="showingSidebar" class="flex-grow text-left uppercase">Gestão de Stock</span>
                            <ChevronDown v-if="showingSidebar" :class="openMenus.stock ? 'rotate-180' : ''" class="w-3 h-3 transition-transform" />
                        </button>
                        <div v-show="openMenus.stock && showingSidebar" class="pl-10 space-y-1 animate-fadeIn">
                            <Link v-if="can('accordionProdutos')" href="#" class="flex items-center py-1.5 text-[11px] opacity-80 hover:opacity-100">
                                <Package class="w-3 h-3 mr-2" /> PRODUTOS
                            </Link>
                            <Link v-if="can('accordionDepositos')" href="#" class="flex items-center py-1.5 text-[11px] opacity-80 hover:opacity-100">
                                <Warehouse class="w-3 h-3 mr-2" /> DEPÓSITOS
                            </Link>
                            <Link v-if="can('accordionEntrada')" href="#" class="flex items-center py-1.5 text-[11px] opacity-80 hover:opacity-100">
                                <ArrowDownToLine class="w-3 h-3 mr-2" /> ENTRADA DE STOCK
                            </Link>
                            <Link v-if="can('accordionBaixa')" href="#" class="flex items-center py-1.5 text-[11px] opacity-80 hover:opacity-100">
                                <ArrowUpFromLine class="w-3 h-3 mr-2" /> BAIXA DE STOCK
                            </Link>
                            <Link v-if="can('accordionDocumentos')" href="#" class="flex items-center py-1.5 text-[11px] opacity-80 hover:opacity-100">
                                <FileStack class="w-3 h-3 mr-2" /> DOCUMENTOS EMITIDOS
                            </Link>
                            <Link v-if="can('accordionRelatorios')" href="#" class="flex items-center py-1.5 text-[11px] opacity-80 hover:opacity-100">
                                <BarChart3 class="w-3 h-3 mr-2" /> RELATÓRIO E ESTATÍSTICA
                            </Link>
                        </div>
                    </div>
                </nav>
            </div>

            <!-- Footer Sidebar -->
            <div class="mt-auto border-t border-white/10 bg-blue-800/10 backdrop-blur-sm">
                <div class="p-4 flex flex-col items-center">
                    <p v-if="showingSidebar" class="text-[10px] font-black uppercase tracking-[0.2em] text-white/40 mb-2">Clínica</p>
                    <div v-if="clinicData?.logo" class="bg-white p-1.5 rounded-lg shadow-lg mb-2">
                        <img :src="clinicData.logo" :class="showingSidebar ? 'h-10' : 'h-6'" class="w-auto object-contain mx-auto" alt="Logo Clínica" />
                    </div>
                    <p v-if="showingSidebar" class="text-[10px] font-black text-white uppercase tracking-tight text-center leading-tight max-w-[140px]">
                        {{ clinicData?.nome || 'EMUTE' }}
                    </p>
                </div>
            </div>
        </aside>

        <!-- Main Content Wrapper -->
        <main 
            :class="showingSidebar ? 'lg:pl-0' : 'lg:pl-0'" 
            class="flex-grow flex flex-col transition-all duration-300 min-w-0 overflow-hidden relative"
        >
            
            <!-- Barra Superior Principal -->
            <header class="shrink-0 h-16 bg-white/80 backdrop-blur-md border-b border-gray-100 flex items-center justify-between px-4 sm:px-6 z-20 shadow-sm">
                <!-- Esquerda: Info e Links -->
                <div class="flex items-center space-x-2 sm:space-x-6 text-xs font-medium text-gray-500">
                    <!-- Mobile Menu Toggle -->
                    <button @click="showingSidebar = !showingSidebar" class="lg:hidden p-2 hover:bg-gray-100 rounded-lg transition-all mr-1">
                        <ChevronRight v-if="!showingSidebar" class="w-5 h-5 text-gray-600" />
                        <X v-else class="w-5 h-5 text-gray-600" />
                    </button>

                    <div class="hidden xs:flex items-center space-x-2 bg-blue-50 text-blue-600 px-2 sm:px-3 py-1.5 rounded-full border border-blue-100 shadow-sm">
                        <span class="relative flex h-2.5 w-2.5">
                          <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                          <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-blue-500"></span>
                        </span>
                        <span class="font-bold tracking-wide uppercase text-[10px]">Online</span>
                    </div>

                    <!-- Nome da Página Atual -->
                    <div class="hidden md:flex items-center ml-2 sm:ml-4 pl-2 sm:pl-4 border-l border-slate-200">
                        <span class="text-xs font-black text-slate-700 uppercase tracking-widest">{{ pageTitle }}</span>
                    </div>
                </div>

                <!-- Direita: Perfil e Ações -->
                <div class="flex items-center space-x-4">
                    <div class="hidden md:flex space-x-4 mr-2 text-xs font-medium text-gray-500">
                        <Link href="#" class="hover:text-blue-600 transition-colors flex items-center"><MonitorSmartphone class="w-3.5 h-3.5 mr-1.5"/> Suporte</Link>
                    </div>
                    
                    <!-- User Dropdown/Pill -->
                    <div class="flex items-center bg-white border border-gray-200 rounded-full p-1 pr-4 shadow-sm hover:shadow-md transition-all cursor-pointer group">
                        <div class="w-8 h-8 rounded-full bg-white flex items-center justify-center shadow-inner mr-3 group-hover:scale-105 transition-transform overflow-hidden border border-slate-100">
                            <img v-if="clinicData?.logo" :src="clinicData.logo" class="w-full h-full object-contain p-1" alt="Logo" />
                            <div v-else class="w-full h-full bg-gradient-to-tr from-blue-600 to-blue-400 flex items-center justify-center text-white">
                                <span class="text-xs font-bold">{{ (user.NOME_UTILIZADOR || user.name || 'E').charAt(0).toUpperCase() }}</span>
                            </div>
                        </div>
                        <div class="hidden sm:flex flex-col justify-center">
                            <span class="text-xs font-bold text-gray-700 leading-tight truncate max-w-[100px]">{{ user.NOME_UTILIZADOR || user.name || 'EMUTE' }}</span>
                            <span class="text-[10px] text-gray-400 font-medium leading-tight">Administrador</span>
                        </div>
                        <div class="ml-2 sm:ml-4 pl-2 sm:pl-4 border-l border-gray-100 flex items-center">
                            <Link :href="route('logout')" method="post" as="button" class="text-gray-400 hover:text-red-500 transition-colors flex items-center justify-center w-6 h-6 rounded-full hover:bg-red-50" title="Terminar Sessão">
                                <LogOut class="w-4 h-4" />
                            </Link>
                        </div>
                    </div>
                </div>
            </header>



            <!-- Page Slots -->
            <div class="flex-grow overflow-y-auto overflow-x-hidden p-4 sm:p-6 custom-scrollbar relative">
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
