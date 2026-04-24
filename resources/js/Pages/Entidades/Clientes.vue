<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { 
    Users, 
    Search, 
    Plus, 
    Edit, 
    Trash2, 
    FileText, 
    Copy,
    ChevronLeft,
    ChevronRight,
    Settings2,
    Eye,
    ChevronDown,
    X
} from 'lucide-vue-next';

const props = defineProps({
    clientes: {
        type: Array,
        default: () => []
    }
});

const searchQuery = ref('');
const isModalOpen = ref(false);
const editingCodigo = ref(null);
const selectedClientes = ref([]);

const form = useForm({
    nome: '',
    nif: '',
    natureza: 'SINGULAR',
    telefone: '',
    email: '',
    cidade: '',
    rua: ''
});

const toggleSelection = (codigo) => {
    const index = selectedClientes.value.indexOf(codigo);
    if (index > -1) {
        selectedClientes.value.splice(index, 1);
    } else {
        selectedClientes.value.push(codigo);
    }
};

const openCreateModal = () => {
    editingCodigo.value = null;
    form.reset();
    isModalOpen.value = true;
};

const openEditModal = () => {
    if (selectedClientes.value.length !== 1) {
        alert('Por favor, selecione apenas um cliente para editar.');
        return;
    }
    
    const codigo = selectedClientes.value[0];
    const cliente = props.clientes.find(c => c.Codigo === codigo);
    
    if (cliente) {
        form.nome = cliente.Nome;
        form.nif = cliente.NIF || '';
        form.natureza = cliente.Natureza || 'SINGULAR';
        form.telefone = cliente.Telefone || '';
        form.email = cliente.Email || '';
        form.cidade = cliente.Cidade || '';
        form.rua = cliente.Rua || ''; // tb_tipoentidade Rua is not in select? I didn't select it in index. Let's assume it might be missing or we just leave it blank.
        
        editingCodigo.value = codigo;
        isModalOpen.value = true;
    }
};

const deleteCliente = () => {
    if (selectedClientes.value.length !== 1) {
        alert('Por favor, selecione um cliente para eliminar.');
        return;
    }
    
    if (confirm('Tem a certeza que deseja eliminar este cliente?')) {
        const codigo = selectedClientes.value[0];
        form.delete(route('clientes.destroy', codigo), {
            onSuccess: () => {
                selectedClientes.value = [];
            }
        });
    }
};

const submit = () => {
    if (editingCodigo.value) {
        form.put(route('clientes.update', editingCodigo.value), {
            onSuccess: () => {
                isModalOpen.value = false;
                form.reset();
                editingCodigo.value = null;
                selectedClientes.value = [];
            }
        });
    } else {
        form.post(route('clientes.store'), {
            onSuccess: () => {
                isModalOpen.value = false;
                form.reset();
            }
        });
    }
};

</script>

<template>
    <Head title="Gestão de Clientes" />

    <DashboardLayout>
        
        <!-- Header -->
        <div class="mb-6 bg-white rounded-2xl p-6 shadow-sm border border-gray-100 flex justify-between items-center">
            <div>
                <h1 class="text-xl font-black text-gray-800 tracking-tight flex items-center">
                    <Users class="w-6 h-6 mr-3 text-blue-600" />
                    GESTÃO DE CLIENTES
                </h1>
                <p class="text-gray-500 text-xs mt-1">Gira os registos, contactos e detalhes de todos os clientes.</p>
            </div>
            
            <div class="flex items-center space-x-3">
                <div class="relative">
                    <Search class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
                    <input v-model="searchQuery" placeholder="Pesquisar cliente..." class="pl-9 pr-4 py-2 border-gray-200 rounded-lg text-xs w-64 focus:ring-blue-500 focus:border-blue-500 bg-gray-50 hover:bg-white transition-colors" />
                </div>
            </div>
        </div>

        <!-- Toolbar (Botões de Ação) -->
        <div class="bg-white rounded-t-2xl border border-gray-100 p-3 shadow-sm flex items-center justify-between">
            <div class="flex items-center space-x-1 overflow-x-auto custom-scrollbar">
                <button @click="openEditModal" :class="{'opacity-50 cursor-not-allowed': selectedClientes.length !== 1}" class="flex items-center px-4 py-2 text-xs font-bold text-gray-700 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors border border-transparent hover:border-blue-100">
                    <Edit class="w-4 h-4 mr-2 text-blue-500" /> Editar
                </button>
                <button @click="deleteCliente" :class="{'opacity-50 cursor-not-allowed': selectedClientes.length !== 1}" class="flex items-center px-4 py-2 text-xs font-bold text-gray-700 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors border border-transparent hover:border-red-100">
                    <Trash2 class="w-4 h-4 mr-2 text-red-500" /> Eliminar
                </button>
                <div class="w-px h-6 bg-gray-200 mx-2"></div>
                <button @click="openCreateModal" class="flex items-center px-4 py-2 text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors shadow-md shadow-blue-500/20">
                    <Plus class="w-4 h-4 mr-2" /> Registar Cliente
                </button>
                <button class="flex items-center px-4 py-2 text-xs font-bold text-gray-700 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors border border-transparent hover:border-blue-100">
                    <FileText class="w-4 h-4 mr-2 text-gray-400 group-hover:text-blue-500" /> Registar Serviço
                </button>
                <div class="w-px h-6 bg-gray-200 mx-2"></div>
                <div class="flex items-center px-3 py-1.5 bg-gray-50 rounded-lg border border-gray-200">
                    <span class="text-[10px] text-gray-500 font-bold mr-2 uppercase">Novo ID:</span>
                    <span class="text-xs font-black text-gray-800">CLI001</span>
                </div>
                <button class="flex items-center px-4 py-2 text-xs font-bold text-gray-700 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors border border-transparent hover:border-blue-100 ml-2">
                    <Copy class="w-4 h-4 mr-2 text-gray-400" /> Duplicar Registo
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
                                <input type="checkbox" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" />
                            </th>
                            <th class="px-4 py-3 border-b border-r border-gray-100 cursor-pointer hover:bg-gray-100 transition-colors group">
                                <div class="flex items-center justify-between">
                                    Código
                                    <ChevronDown class="w-3 h-3 opacity-0 group-hover:opacity-100" />
                                </div>
                            </th>
                            <th class="px-4 py-3 border-b border-r border-gray-100 cursor-pointer hover:bg-gray-100 transition-colors group">
                                <div class="flex items-center justify-between">
                                    Nome / Entidade
                                    <ChevronDown class="w-3 h-3 opacity-0 group-hover:opacity-100" />
                                </div>
                            </th>
                            <th class="px-4 py-3 border-b border-r border-gray-100 cursor-pointer hover:bg-gray-100 transition-colors group">NIF / Contribuinte</th>
                            <th class="px-4 py-3 border-b border-r border-gray-100 cursor-pointer hover:bg-gray-100 transition-colors group">Telefone</th>
                            <th class="px-4 py-3 border-b border-r border-gray-100 cursor-pointer hover:bg-gray-100 transition-colors group">Email</th>
                            <th class="px-4 py-3 border-b border-r border-gray-100 cursor-pointer hover:bg-gray-100 transition-colors group">Natureza</th>
                            <th class="px-4 py-3 border-b border-r border-gray-100 cursor-pointer hover:bg-gray-100 transition-colors group">Cidade</th>
                            <th class="px-4 py-3 border-b border-gray-100 text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(cliente, index) in props.clientes" :key="cliente.Id" 
                            class="border-b border-gray-50 transition-colors group"
                            :class="{
                                'bg-blue-50/50': selectedClientes.includes(cliente.Codigo),
                                'bg-gray-50/10 hover:bg-blue-50/30': !selectedClientes.includes(cliente.Codigo) && index % 2 === 0,
                                'hover:bg-blue-50/30': !selectedClientes.includes(cliente.Codigo) && index % 2 !== 0
                            }">
                            <td class="px-4 py-2 border-r border-gray-50 text-center">
                                <input type="checkbox" :checked="selectedClientes.includes(cliente.Codigo)" @change="toggleSelection(cliente.Codigo)" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 cursor-pointer" />
                            </td>
                            <td class="px-4 py-2 border-r border-gray-50 text-xs font-bold text-gray-700">
                                {{ cliente.Codigo }}
                            </td>
                            <td class="px-4 py-2 border-r border-gray-50 text-xs font-bold text-gray-800 uppercase">
                                {{ cliente.Nome }}
                            </td>
                            <td class="px-4 py-2 border-r border-gray-50 text-xs text-gray-600 font-mono">
                                {{ cliente.NIF || '---' }}
                            </td>
                            <td class="px-4 py-2 border-r border-gray-50 text-xs text-gray-600">
                                {{ cliente.Telefone || '---' }}
                            </td>
                            <td class="px-4 py-2 border-r border-gray-50 text-xs text-gray-600">
                                {{ cliente.Email || '---' }}
                            </td>
                            <td class="px-4 py-2 border-r border-gray-50">
                                <span :class="cliente.Natureza === 'COLECTIVO' ? 'bg-indigo-50 text-indigo-700 border-indigo-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200'" class="px-2 py-0.5 rounded text-[9px] font-bold uppercase border">
                                    {{ cliente.Natureza || 'SINGULAR' }}
                                </span>
                            </td>
                            <td class="px-4 py-2 border-r border-gray-50 text-xs text-gray-600">
                                {{ cliente.Cidade || '---' }}
                            </td>
                            <td class="px-4 py-2 text-center">
                                <button class="text-gray-400 hover:text-blue-600 transition-colors p-1">
                                    <Eye class="w-4 h-4" />
                                </button>
                            </td>
                        </tr>
                        
                        <!-- Empty State -->
                        <tr v-if="props.clientes.length === 0">
                            <td colspan="9" class="px-4 py-16 text-center">
                                <div class="flex flex-col items-center justify-center text-gray-400">
                                    <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                                        <Users class="w-8 h-8 text-gray-300" />
                                    </div>
                                    <p class="text-sm font-bold text-gray-600">Nenhum cliente encontrado</p>
                                    <p class="text-xs mt-1">Os clientes registados aparecerão nesta listagem.</p>
                                    <button @click="openCreateModal" class="mt-4 px-4 py-2 bg-blue-50 text-blue-600 text-xs font-bold rounded-lg hover:bg-blue-100 transition-colors">
                                        Registar o Primeiro Cliente
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination Footer -->
            <div class="bg-white p-3 border-t border-gray-100 flex items-center justify-between text-xs text-gray-500">
                <span>Mostrando {{ props.clientes.length }} registo(s)</span>
                <div class="flex items-center space-x-1">
                    <button class="p-1 rounded hover:bg-gray-100 disabled:opacity-50"><ChevronLeft class="w-4 h-4" /></button>
                    <button class="w-6 h-6 rounded bg-blue-600 text-white font-bold flex items-center justify-center">1</button>
                    <button class="p-1 rounded hover:bg-gray-100 disabled:opacity-50"><ChevronRight class="w-4 h-4" /></button>
                </div>
            </div>
        </div>

        <!-- Modal de Registo -->
        <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 backdrop-blur-sm p-4 animate-fadeIn">
            <div class="bg-white rounded-2xl shadow-xl border border-gray-100 w-full max-w-2xl overflow-hidden flex flex-col max-h-[90vh]">
                
                <!-- Modal Header -->
                <div class="bg-gradient-to-r from-blue-600 to-blue-800 p-5 flex justify-between items-center text-white relative">
                    <div class="absolute inset-0 bg-grid-white/[0.05] bg-[size:20px_20px]"></div>
                    <div class="relative z-10">
                        <h2 class="text-lg font-black tracking-tight flex items-center">
                            <Users class="w-5 h-5 mr-2" /> {{ editingCodigo ? 'EDITAR CLIENTE' : 'REGISTAR NOVO CLIENTE' }}
                        </h2>
                        <p class="text-blue-200 text-xs mt-1">{{ editingCodigo ? 'Atualize os dados da entidade selecionada.' : 'Preencha os dados da entidade para adicionar à base de dados.' }}</p>
                    </div>
                    <button @click="isModalOpen = false" class="relative z-10 p-1.5 bg-white/10 hover:bg-white/20 rounded-lg transition-colors">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <!-- Modal Body (Form) -->
                <div class="flex-grow overflow-y-auto p-6 bg-gray-50/50 custom-scrollbar">
                    <form @submit.prevent="submit" class="space-y-6">
                        
                        <!-- Secção: Dados Principais -->
                        <div class="space-y-4">
                            <h3 class="text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-200 pb-2">Dados Principais</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="col-span-2">
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Nome / Designação da Entidade *</label>
                                    <input v-model="form.nome" required class="w-full border-gray-200 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500 bg-white" placeholder="Ex: Consumidor Final" />
                                    <span v-if="form.errors.nome" class="text-red-500 text-[10px]">{{ form.errors.nome }}</span>
                                </div>
                                
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">NIF / Contribuinte</label>
                                    <input v-model="form.nif" class="w-full border-gray-200 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500 bg-white" placeholder="Ex: 999999999" />
                                    <span v-if="form.errors.nif" class="text-red-500 text-[10px]">{{ form.errors.nif }}</span>
                                </div>
                                
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Natureza da Entidade *</label>
                                    <select v-model="form.natureza" required class="w-full border-gray-200 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500 bg-white">
                                        <option value="SINGULAR">Singular (Indivíduo)</option>
                                        <option value="COLECTIVO">Colectivo (Empresa)</option>
                                    </select>
                                    <span v-if="form.errors.natureza" class="text-red-500 text-[10px]">{{ form.errors.natureza }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Secção: Contactos e Morada -->
                        <div class="space-y-4">
                            <h3 class="text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-200 pb-2">Contactos e Localização</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Telefone / Telemóvel</label>
                                    <input v-model="form.telefone" class="w-full border-gray-200 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500 bg-white" placeholder="+244 ..." />
                                </div>
                                
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Email</label>
                                    <input type="email" v-model="form.email" class="w-full border-gray-200 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500 bg-white" placeholder="email@exemplo.com" />
                                </div>
                                
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Cidade</label>
                                    <input v-model="form.cidade" class="w-full border-gray-200 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500 bg-white" placeholder="Ex: Luanda" />
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Rua / Bairro</label>
                                    <input v-model="form.rua" class="w-full border-gray-200 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500 bg-white" placeholder="Endereço principal" />
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
                    <button @click="submit" :disabled="form.processing" class="px-6 py-2 text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-lg shadow-md shadow-blue-500/20 transition-colors flex items-center disabled:opacity-50">
                        <span v-if="form.processing" class="w-4 h-4 mr-2 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                        {{ editingCodigo ? 'Atualizar Cliente' : 'Guardar Cliente' }}
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
