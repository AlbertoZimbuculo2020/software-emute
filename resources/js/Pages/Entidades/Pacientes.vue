<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { 
    HeartPulse, 
    Search, 
    Plus, 
    Edit, 
    Trash2, 
    Download,
    ChevronLeft,
    ChevronRight,
    Settings2,
    Eye,
    ChevronDown,
    X,
    UserCircle2,
    Building,
    Calendar,
    Phone,
    Mail
} from 'lucide-vue-next';

const props = defineProps({
    pacientes: {
        type: Array,
        default: () => []
    }
});

const searchQuery = ref('');
const isModalOpen = ref(false);
const editingCodigo = ref(null);
const selectedPacientes = ref([]);

const form = useForm({
    nome: '',
    nif: '',
    data_nascimento: '',
    genero: '',
    telefone: '',
    email: '',
    cidade: '',
    rua: '',
    seguradora: '',
    pai: '',
    mae: ''
});

const toggleSelection = (codigo) => {
    const index = selectedPacientes.value.indexOf(codigo);
    if (index > -1) {
        selectedPacientes.value.splice(index, 1);
    } else {
        selectedPacientes.value.push(codigo);
    }
};

const openCreateModal = () => {
    editingCodigo.value = null;
    form.reset();
    isModalOpen.value = true;
};

const openEditModal = () => {
    if (selectedPacientes.value.length !== 1) {
        alert('Por favor, selecione apenas um paciente para editar.');
        return;
    }
    
    const codigo = selectedPacientes.value[0];
    const paciente = props.pacientes.find(p => p.Codigo === codigo);
    
    if (paciente) {
        form.nome = paciente.Nome;
        form.nif = paciente.NIF || '';
        form.data_nascimento = paciente.DataNascimento || '';
        form.genero = paciente.Genero || '';
        form.telefone = paciente.Telefone || '';
        form.email = paciente.Email || '';
        form.cidade = paciente.Cidade || '';
        form.rua = paciente.Rua || '';
        form.seguradora = paciente.Seguradora || '';
        form.pai = paciente.Pai || '';
        form.mae = paciente.Mae || '';
        
        editingCodigo.value = codigo;
        isModalOpen.value = true;
    }
};

const deletePaciente = () => {
    if (selectedPacientes.value.length !== 1) {
        alert('Por favor, selecione um paciente para eliminar.');
        return;
    }
    
    if (confirm('Tem a certeza que deseja eliminar este paciente?')) {
        const codigo = selectedPacientes.value[0];
        form.delete(route('pacientes.destroy', codigo), {
            onSuccess: () => {
                selectedPacientes.value = [];
            }
        });
    }
};

const submit = () => {
    if (editingCodigo.value) {
        form.put(route('pacientes.update', editingCodigo.value), {
            onSuccess: () => {
                isModalOpen.value = false;
                form.reset();
                editingCodigo.value = null;
                selectedPacientes.value = [];
            }
        });
    } else {
        form.post(route('pacientes.store'), {
            onSuccess: () => {
                isModalOpen.value = false;
                form.reset();
            }
        });
    }
};

</script>

<template>
    <Head title="Gestão de Pacientes" />

    <DashboardLayout>
        
        <!-- Header -->
        <div class="mb-6 bg-white rounded-2xl p-6 shadow-sm border border-gray-100 flex justify-between items-center">
            <div>
                <h1 class="text-xl font-black text-gray-800 tracking-tight flex items-center">
                    <HeartPulse class="w-6 h-6 mr-3 text-red-500" />
                    GESTÃO DE PACIENTES
                </h1>
                <p class="text-gray-500 text-xs mt-1">Gira os processos clínicos, filiação e seguros de saúde dos pacientes.</p>
            </div>
            
            <div class="flex items-center space-x-3">
                <div class="relative">
                    <Search class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
                    <input v-model="searchQuery" placeholder="Pesquisar paciente..." class="pl-9 pr-4 py-2 border-gray-200 rounded-lg text-xs w-64 focus:ring-red-500 focus:border-red-500 bg-gray-50 hover:bg-white transition-colors" />
                </div>
            </div>
        </div>

        <!-- Toolbar (Botões de Ação) -->
        <div class="bg-white rounded-t-2xl border border-gray-100 p-3 shadow-sm flex items-center justify-between">
            <div class="flex items-center space-x-1 overflow-x-auto custom-scrollbar">
                <button @click="openEditModal" :class="{'opacity-50 cursor-not-allowed': selectedPacientes.length !== 1}" class="flex items-center px-4 py-2 text-xs font-bold text-gray-700 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors border border-transparent hover:border-red-100">
                    <Edit class="w-4 h-4 mr-2 text-red-500" /> Editar
                </button>
                <button @click="deletePaciente" :class="{'opacity-50 cursor-not-allowed': selectedPacientes.length !== 1}" class="flex items-center px-4 py-2 text-xs font-bold text-gray-700 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors border border-transparent hover:border-red-100">
                    <Trash2 class="w-4 h-4 mr-2 text-red-500" /> Eliminar
                </button>
                <div class="w-px h-6 bg-gray-200 mx-2"></div>
                <button @click="openCreateModal" class="flex items-center px-4 py-2 text-xs font-bold text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors shadow-md shadow-red-500/20">
                    <Plus class="w-4 h-4 mr-2" /> Registar Paciente
                </button>
                <div class="w-px h-6 bg-gray-200 mx-2"></div>
                <button class="flex items-center px-4 py-2 text-xs font-bold text-gray-700 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors border border-transparent hover:border-red-100">
                    <Download class="w-4 h-4 mr-2 text-gray-400 group-hover:text-red-500" /> Exportar Pacientes
                </button>
            </div>
            
            <div class="flex items-center space-x-2">
                <button class="p-2 text-gray-400 hover:text-gray-700 bg-gray-50 hover:bg-gray-100 rounded-lg transition-colors">
                    <Settings2 class="w-4 h-4" />
                </button>
            </div>
        </div>

        <!-- Data Grid -->
        <div class="bg-white rounded-b-2xl border-x border-b border-gray-100 shadow-sm overflow-hidden flex flex-col mb-6">
            <div class="bg-gray-50/80 px-4 py-2 border-b border-gray-100 text-[10px] text-gray-400 italic font-medium flex items-center justify-between">
                <span>Arraste o cabeçalho de uma coluna para aqui para agrupar por essa coluna</span>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse whitespace-nowrap">
                    <thead class="bg-gray-50/50">
                        <tr class="text-[10px] font-black text-gray-500 uppercase tracking-wider">
                            <th class="px-4 py-3 border-b border-r border-gray-100 w-10 text-center">
                                <input type="checkbox" class="rounded border-gray-300 text-red-600 focus:ring-red-500" />
                            </th>
                            <th class="px-4 py-3 border-b border-r border-gray-100 cursor-pointer hover:bg-gray-100 transition-colors group">Código <ChevronDown class="inline w-3 h-3 opacity-0 group-hover:opacity-100" /></th>
                            <th class="px-4 py-3 border-b border-r border-gray-100 cursor-pointer hover:bg-gray-100 transition-colors group">Nome <ChevronDown class="inline w-3 h-3 opacity-0 group-hover:opacity-100" /></th>
                            <th class="px-4 py-3 border-b border-r border-gray-100 cursor-pointer hover:bg-gray-100 transition-colors group">Contribuinte</th>
                            <th class="px-4 py-3 border-b border-r border-gray-100 cursor-pointer hover:bg-gray-100 transition-colors group">Data Nascim...</th>
                            <th class="px-4 py-3 border-b border-r border-gray-100 cursor-pointer hover:bg-gray-100 transition-colors group">Telefone</th>
                            <th class="px-4 py-3 border-b border-r border-gray-100 cursor-pointer hover:bg-gray-100 transition-colors group">Cidade</th>
                            <th class="px-4 py-3 border-b border-r border-gray-100 cursor-pointer hover:bg-gray-100 transition-colors group">Rua</th>
                            <th class="px-4 py-3 border-b border-r border-gray-100 cursor-pointer hover:bg-gray-100 transition-colors group">Seguradora</th>
                            <th class="px-4 py-3 border-b border-r border-gray-100 cursor-pointer hover:bg-gray-100 transition-colors group">Primeira Co...</th>
                            <th class="px-4 py-3 border-b border-gray-100 cursor-pointer hover:bg-gray-100 transition-colors group">Ultima Consu...</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(paciente, index) in props.pacientes" :key="paciente.Id" 
                            class="border-b border-gray-50 transition-colors group"
                            :class="{
                                'bg-red-50/50': selectedPacientes.includes(paciente.Codigo),
                                'bg-gray-50/10 hover:bg-red-50/30': !selectedPacientes.includes(paciente.Codigo) && index % 2 === 0,
                                'hover:bg-red-50/30': !selectedPacientes.includes(paciente.Codigo) && index % 2 !== 0
                            }">
                            <td class="px-4 py-2 border-r border-gray-50 text-center">
                                <input type="checkbox" :checked="selectedPacientes.includes(paciente.Codigo)" @change="toggleSelection(paciente.Codigo)" class="rounded border-gray-300 text-red-600 focus:ring-red-500 cursor-pointer" />
                            </td>
                            <td class="px-4 py-2 border-r border-gray-50 text-xs font-bold text-gray-700">
                                {{ paciente.Codigo }}
                            </td>
                            <td class="px-4 py-2 border-r border-gray-50 text-xs font-bold text-gray-800 uppercase">
                                {{ paciente.Nome }}
                            </td>
                            <td class="px-4 py-2 border-r border-gray-50 text-xs text-gray-600 font-mono">
                                {{ paciente.NIF || '---' }}
                            </td>
                            <td class="px-4 py-2 border-r border-gray-50 text-xs text-gray-600">
                                {{ paciente.DataNascimento ? new Date(paciente.DataNascimento).toLocaleDateString('pt-PT') : '---' }}
                            </td>
                            <td class="px-4 py-2 border-r border-gray-50 text-xs text-gray-600">
                                {{ paciente.Telefone || '---' }}
                            </td>
                            <td class="px-4 py-2 border-r border-gray-50 text-xs text-gray-600">
                                {{ paciente.Cidade || '---' }}
                            </td>
                            <td class="px-4 py-2 border-r border-gray-50 text-xs text-gray-600 truncate max-w-[150px]">
                                {{ paciente.Rua || '---' }}
                            </td>
                            <td class="px-4 py-2 border-r border-gray-50">
                                <span v-if="paciente.Seguradora" class="px-2 py-0.5 bg-blue-50 text-blue-700 border border-blue-200 rounded text-[9px] font-bold uppercase">
                                    {{ paciente.Seguradora }}
                                </span>
                                <span v-else class="text-xs text-gray-400 italic">Particular</span>
                            </td>
                            <td class="px-4 py-2 border-r border-gray-50 text-xs text-gray-400">---</td>
                            <td class="px-4 py-2 border-r border-gray-50 text-xs text-gray-400">---</td>
                        </tr>
                        
                        <!-- Empty State -->
                        <tr v-if="props.pacientes.length === 0">
                            <td colspan="11" class="px-4 py-16 text-center">
                                <div class="flex flex-col items-center justify-center text-gray-400">
                                    <div class="w-16 h-16 bg-red-50 rounded-full flex items-center justify-center mb-4">
                                        <HeartPulse class="w-8 h-8 text-red-300" />
                                    </div>
                                    <p class="text-sm font-bold text-gray-600">Nenhum paciente encontrado</p>
                                    <p class="text-xs mt-1">Os pacientes registados aparecerão nesta listagem.</p>
                                    <button @click="openCreateModal" class="mt-4 px-4 py-2 bg-red-50 text-red-600 text-xs font-bold rounded-lg hover:bg-red-100 transition-colors">
                                        Registar o Primeiro Paciente
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination Footer -->
            <div class="bg-white p-3 border-t border-gray-100 flex items-center justify-between text-xs text-gray-500">
                <span>Mostrando {{ props.pacientes.length }} registo(s)</span>
                <div class="flex items-center space-x-1">
                    <button class="p-1 rounded hover:bg-gray-100 disabled:opacity-50"><ChevronLeft class="w-4 h-4" /></button>
                    <button class="w-6 h-6 rounded bg-red-600 text-white font-bold flex items-center justify-center">1</button>
                    <button class="p-1 rounded hover:bg-gray-100 disabled:opacity-50"><ChevronRight class="w-4 h-4" /></button>
                </div>
            </div>
        </div>

        <!-- Modal de Registo/Edição -->
        <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 backdrop-blur-sm p-4 animate-fadeIn">
            <div class="bg-white rounded-2xl shadow-xl border border-gray-100 w-full max-w-3xl overflow-hidden flex flex-col max-h-[90vh]">
                
                <!-- Modal Header -->
                <div class="bg-gradient-to-r from-red-500 to-red-700 p-5 flex justify-between items-center text-white relative">
                    <div class="absolute inset-0 bg-grid-white/[0.05] bg-[size:20px_20px]"></div>
                    <div class="relative z-10">
                        <h2 class="text-lg font-black tracking-tight flex items-center">
                            <HeartPulse class="w-5 h-5 mr-2" /> {{ editingCodigo ? 'EDITAR PACIENTE' : 'REGISTAR NOVO PACIENTE' }}
                        </h2>
                        <p class="text-red-100 text-xs mt-1">Preencha o processo clínico e os dados pessoais do paciente.</p>
                    </div>
                    <button @click="isModalOpen = false" class="relative z-10 p-1.5 bg-white/10 hover:bg-white/20 rounded-lg transition-colors">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <!-- Modal Body (Form) -->
                <div class="flex-grow overflow-y-auto p-6 bg-gray-50/50 custom-scrollbar">
                    <form @submit.prevent="submit" class="space-y-6">
                        
                        <!-- Secção: Dados Pessoais -->
                        <div class="space-y-4">
                            <h3 class="text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-200 pb-2 flex items-center">
                                <UserCircle2 class="w-3 h-3 mr-1" /> Dados Pessoais
                            </h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Nome Completo do Paciente *</label>
                                    <input v-model="form.nome" required class="w-full border-gray-200 rounded-lg text-sm focus:ring-red-500 focus:border-red-500 bg-white" placeholder="Ex: João Silva" />
                                    <span v-if="form.errors.nome" class="text-red-500 text-[10px]">{{ form.errors.nome }}</span>
                                </div>
                                
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">NIF / BI</label>
                                    <input v-model="form.nif" class="w-full border-gray-200 rounded-lg text-sm focus:ring-red-500 focus:border-red-500 bg-white" placeholder="Nº Documento" />
                                </div>
                                
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Data de Nascimento</label>
                                    <input type="date" v-model="form.data_nascimento" class="w-full border-gray-200 rounded-lg text-sm focus:ring-red-500 focus:border-red-500 bg-white text-gray-600" />
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Género</label>
                                    <select v-model="form.genero" class="w-full border-gray-200 rounded-lg text-sm focus:ring-red-500 focus:border-red-500 bg-white">
                                        <option value="">Selecione...</option>
                                        <option value="Masculino">Masculino</option>
                                        <option value="Feminino">Feminino</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Seguradora (Opcional)</label>
                                    <input v-model="form.seguradora" class="w-full border-gray-200 rounded-lg text-sm focus:ring-red-500 focus:border-red-500 bg-white" placeholder="Nome da Seguradora" />
                                </div>
                            </div>
                        </div>

                        <!-- Secção: Filiação -->
                        <div class="space-y-4">
                            <h3 class="text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-200 pb-2">Filiação</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Nome do Pai</label>
                                    <input v-model="form.pai" class="w-full border-gray-200 rounded-lg text-sm focus:ring-red-500 focus:border-red-500 bg-white" placeholder="Nome do Pai" />
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Nome da Mãe</label>
                                    <input v-model="form.mae" class="w-full border-gray-200 rounded-lg text-sm focus:ring-red-500 focus:border-red-500 bg-white" placeholder="Nome da Mãe" />
                                </div>
                            </div>
                        </div>

                        <!-- Secção: Contactos e Morada -->
                        <div class="space-y-4">
                            <h3 class="text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-200 pb-2 flex items-center">
                                <Phone class="w-3 h-3 mr-1" /> Contactos e Localização
                            </h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Telefone / Telemóvel</label>
                                    <input v-model="form.telefone" class="w-full border-gray-200 rounded-lg text-sm focus:ring-red-500 focus:border-red-500 bg-white" placeholder="+244 ..." />
                                </div>
                                
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Email</label>
                                    <input type="email" v-model="form.email" class="w-full border-gray-200 rounded-lg text-sm focus:ring-red-500 focus:border-red-500 bg-white" placeholder="email@exemplo.com" />
                                </div>
                                
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Cidade</label>
                                    <input v-model="form.cidade" class="w-full border-gray-200 rounded-lg text-sm focus:ring-red-500 focus:border-red-500 bg-white" placeholder="Ex: Luanda" />
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Rua / Bairro</label>
                                    <input v-model="form.rua" class="w-full border-gray-200 rounded-lg text-sm focus:ring-red-500 focus:border-red-500 bg-white" placeholder="Endereço principal" />
                                </div>
                            </div>
                        </div>

                    </form>
                </div>

                <!-- Modal Footer -->
                <div class="bg-white p-4 border-t border-gray-100 flex justify-end space-x-3">
                    <button @click="isModalOpen = false" class="px-4 py-2 text-xs font-bold text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                        Cancelar
                    </button>
                    <button @click="submit" :disabled="form.processing" class="px-6 py-2 text-xs font-bold text-white bg-red-600 hover:bg-red-700 rounded-lg shadow-md shadow-red-500/20 transition-colors flex items-center disabled:opacity-50">
                        <span v-if="form.processing" class="w-4 h-4 mr-2 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                        {{ editingCodigo ? 'Atualizar Paciente' : 'Guardar Paciente' }}
                    </button>
                </div>
            </div>
        </div>

    </DashboardLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    height: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
}
</style>
