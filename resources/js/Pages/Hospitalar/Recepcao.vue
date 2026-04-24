<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import axios from 'axios';
import { 
    Search, 
    UserPlus, 
    Plus, 
    ClipboardList, 
    Stethoscope, 
    Calendar,
    MousePointer2,
    RotateCcw
} from 'lucide-vue-next';
const props = defineProps({
    medicos: Array,
    consultas: Array,
    seguradoras: Array,
    agendamentos: Array
});

const form = ref({
    codigo: '',
    nome: '',
    filiacao_pai: '',
    filiacao_mae: '',
    data_nascimento: new Date().toISOString().split('T')[0],
    idade: 0,
    telefone: '',
    sexo: 'MASCULINO',
    endereco: '',
    tipo_paciente: 'Particular',
    seguradora: '',
    consulta: '',
    medico: '',
    id_paciente_real: null // To store the correct ID from tb_paciente
});

const searchLoading = ref(false);
const searchTerm = ref('');

const buscarPaciente = async () => {
    if (!searchTerm.value) return;
    searchLoading.value = true;
    try {
        const response = await axios.post(route('hospitalar.recepcao.search'), { term: searchTerm.value });
        if (response.data && response.data.length > 0) {
            const p = response.data[0];
            form.value.id_paciente_real = p.Id;
            form.value.codigo = p.Id;
            form.value.nome = p.Nome;
            form.value.telefone = p.Telefone;
            form.value.endereco = p.Endereco;
            // Map other fields if necessary
        } else {
            alert('Paciente não encontrado');
        }
    } catch (error) {
        console.error(error);
    } finally {
        searchLoading.value = false;
    }
};

const agendarConsulta = () => {
    if (!form.value.id_paciente_real) {
        alert('Selecione um paciente primeiro');
        return;
    }

    const data = {
        IdPaciente: form.value.id_paciente_real,
        IdMedico: form.value.medico,
        IdConsulta: form.value.consulta,
        DataAgendamento: form.value.data_nascimento, // or add a separate field for appointment date
        IdSeguradora: form.value.tipo_paciente === 'Assegurado' ? form.value.seguradora : null,
    };

    router.post(route('hospitalar.recepcao.store'), data, {
        onSuccess: () => alert('Consulta agendada com sucesso!'),
        onError: (err) => console.log(err)
    });
};


</script>

<template>
    <Head title="Recepção - Marcação de Consulta" />

    <DashboardLayout>
        <div class="flex flex-col h-full bg-[#E0E5EC] rounded-xl overflow-hidden border border-gray-300 shadow-2xl font-sans text-gray-800">
            


            <!-- Page Concept Header -->
            <div class="bg-[#000080] border-t border-blue-900 py-2 flex flex-col items-center">
                <h1 class="text-white text-xl font-bold tracking-widest uppercase">MARCAÇÃO DE CONSULTA</h1>
                <p class="text-white text-[10px] tracking-widest uppercase opacity-80">AGEO</p>
            </div>

            <!-- Main Content Area -->
            <div class="p-3 grid grid-cols-1 lg:grid-cols-2 gap-4">
                
                <!-- Left Column: Patient Data -->
                <div class="space-y-4">
                    <div class="bg-gray-200 border border-gray-400 rounded-sm p-4 relative pt-6 shadow-inner">
                        <span class="absolute -top-3 left-6 bg-gray-200 px-2 text-xs font-bold text-gray-600 border-x border-t border-gray-400 rounded-t-sm">Dados do Paciente</span>
                        
                        <!-- Actions -->
                        <div class="flex items-center space-x-2 mb-4">
                            <input v-model="searchTerm" placeholder="Nome ou ID..." @keyup.enter="buscarPaciente" class="border border-gray-400 rounded-md px-3 py-1.5 text-xs w-48 shadow-sm focus:border-blue-500" />
                            <button @click="buscarPaciente" :disabled="searchLoading" class="flex items-center bg-[#4FA6FF] hover:bg-blue-500 text-white px-4 py-1.5 rounded-md text-[11px] font-bold shadow-sm transition-all border border-blue-600 group disabled:opacity-50">
                                <Search v-if="!searchLoading" class="w-4 h-4 mr-2 group-hover:scale-110 transition-transform" />
                                <span v-else class="w-4 h-4 mr-2 animate-spin rounded-full border-2 border-white border-t-transparent"></span>
                                BUSCAR PACIENTE
                            </button>
                            <button class="flex items-center bg-[#4FA6FF] hover:bg-blue-500 text-white px-4 py-1.5 rounded-md text-[11px] font-bold shadow-sm transition-all border border-blue-600 group">
                                <UserPlus class="w-4 h-4 mr-2 group-hover:scale-110 transition-transform" /> NOVO PACIENTE
                            </button>
                            <div class="flex items-center ml-auto">
                                <span class="text-xs font-bold mr-2">Código</span>
                                <input v-model="form.codigo" readonly class="w-24 bg-gray-100 border border-gray-400 rounded-sm px-2 py-1 text-xs text-center font-bold" />
                            </div>
                        </div>

                        <!-- Form Fields -->
                        <div class="space-y-3">
                            <div class="grid grid-cols-12 gap-2 items-center">
                                <label class="col-span-2 text-xs font-bold">Nome do Paciente</label>
                                <input v-model="form.nome" class="col-span-10 border border-gray-400 rounded-sm px-2 py-1.5 text-xs bg-white focus:border-blue-500 focus:ring-0" />
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div class="grid grid-cols-12 gap-2 items-center">
                                    <label class="col-span-2 text-xs font-bold">Filiação:</label>
                                    <div class="col-span-10 flex items-center space-x-2 italic text-gray-400 text-[10px]">
                                        <div class="flex-grow">
                                            <span class="block mb-0.5">Pai</span>
                                            <input v-model="form.filiacao_pai" class="w-full border border-gray-400 rounded-sm px-2 py-1.5 text-xs bg-white text-gray-800 italic-none" />
                                        </div>
                                        <div class="flex-grow">
                                            <span class="block mb-0.5">Mãe</span>
                                            <input v-model="form.filiacao_mae" class="w-full border border-gray-400 rounded-sm px-2 py-1.5 text-xs bg-white text-gray-800" />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-12 gap-4">
                                <div class="col-span-6 grid grid-cols-4 gap-2 items-center">
                                    <label class="col-span-2 text-xs font-bold whitespace-nowrap">Data de Nascimento</label>
                                    <input type="date" v-model="form.data_nascimento" class="col-span-2 border border-gray-400 rounded-sm px-2 py-1.5 text-xs bg-white" />
                                </div>
                                <div class="col-span-6 grid grid-cols-6 gap-2 items-center">
                                    <label class="col-span-1 text-xs font-bold">Idade</label>
                                    <input v-model="form.idade" class="col-span-1 border border-gray-400 rounded-sm px-2 py-1 text-xs bg-white text-center" />
                                    <span class="col-span-1 text-xs font-bold">D</span>
                                </div>
                            </div>

                            <div class="grid grid-cols-12 gap-4">
                                <div class="col-span-6 grid grid-cols-4 gap-2 items-center">
                                    <label class="col-span-2 text-xs font-bold">Telefone</label>
                                    <input v-model="form.telefone" class="col-span-2 border border-gray-400 rounded-sm px-2 py-1.5 text-xs bg-white" />
                                </div>
                                <div class="col-span-6 grid grid-cols-4 gap-2 items-center">
                                    <label class="col-span-1 text-xs font-bold">Sexo</label>
                                    <select v-model="form.sexo" class="col-span-3 border border-gray-400 rounded-sm px-2 py-1.5 text-xs bg-white">
                                        <option>MASCULINO</option>
                                        <option>FEMININO</option>
                                    </select>
                                </div>
                            </div>

                            <div class="grid grid-cols-12 gap-2 items-center">
                                <label class="col-span-2 text-xs font-bold">Endereço</label>
                                <div class="col-span-10 flex">
                                    <input v-model="form.endereco" class="flex-grow border border-gray-400 rounded-sm px-2 py-1.5 text-xs bg-white" />
                                    <button class="ml-1 bg-gray-100 border border-gray-400 px-3 text-[10px] uppercase font-bold text-gray-600">SEM</button>
                                </div>
                            </div>

                            <div class="grid grid-cols-12 gap-2 items-center pt-2">
                                <label class="col-span-2 text-xs font-bold">Tipo de Paciente:</label>
                                <div class="col-span-10 flex space-x-12">
                                    <label class="flex items-center space-x-2 text-xs cursor-pointer">
                                        <input type="radio" value="Particular" v-model="form.tipo_paciente" class="w-4 h-4 text-blue-600 border-gray-400 focus:ring-0" />
                                        <span>Particular</span>
                                    </label>
                                    <label class="flex items-center space-x-2 text-xs cursor-pointer">
                                        <input type="radio" value="Assegurado" v-model="form.tipo_paciente" class="w-4 h-4 text-blue-600 border-gray-400 focus:ring-0" />
                                        <span>Assegurado</span>
                                    </label>
                                </div>
                            </div>

                            <div class="grid grid-cols-12 gap-2 items-center">
                                <label class="col-span-2 text-xs font-bold">Seguradora</label>
                                <div class="col-span-10 flex">
                                    <select v-model="form.seguradora" :disabled="form.tipo_paciente !== 'Assegurado'" class="flex-grow border border-gray-400 rounded-sm px-2 py-1.5 text-xs bg-white disabled:bg-gray-100">
                                        <option value="">Selecione...</option>
                                        <option v-for="s in props.seguradoras" :key="s.Id" :value="s.Id">{{ s.Nome }}</option>
                                    </select>
                                    <button class="ml-1 flex items-center justify-center bg-gray-100 border border-gray-400 px-2 py-1 group hover:bg-blue-50">
                                        <Plus class="w-3.5 h-3.5 text-green-600" />
                                        <span class="ml-1 text-[9px] font-bold uppercase text-gray-500">Adicionar</span>
                                    </button>
                                </div>
                            </div>

                            <div class="grid grid-cols-12 gap-2 items-center">
                                <label class="col-span-2 text-xs font-bold">Consulta</label>
                                <div class="col-span-10 flex">
                                    <select v-model="form.consulta" class="flex-grow border border-gray-400 rounded-sm px-2 py-1.5 text-xs bg-white">
                                        <option value="">Selecione...</option>
                                        <option v-for="c in props.consultas" :key="c.Id" :value="c.Id">{{ c.Descricao }} - {{ c.Valor }} KZ</option>
                                    </select>
                                    <button class="ml-1 flex items-center justify-center bg-gray-100 border border-gray-400 px-2 py-1 group hover:bg-blue-50">
                                        <Plus class="w-3.5 h-3.5 text-green-600" />
                                        <span class="ml-1 text-[9px] font-bold uppercase text-gray-500">Adicionar</span>
                                    </button>
                                </div>
                            </div>

                            <div class="grid grid-cols-12 gap-2 items-center">
                                <label class="col-span-2 text-xs font-bold">Médico</label>
                                <div class="col-span-10 flex">
                                    <select v-model="form.medico" class="flex-grow border border-gray-400 rounded-sm px-2 py-1.5 text-xs bg-white">
                                        <option value="">Selecione...</option>
                                        <option v-for="m in props.medicos" :key="m.Id" :value="m.Id">{{ m.Nome }}</option>
                                    </select>
                                    <button class="ml-1 flex items-center justify-center bg-gray-100 border border-gray-400 px-2 py-1 group hover:bg-blue-50">
                                        <Plus class="w-3.5 h-3.5 text-green-600" />
                                        <span class="ml-1 text-[9px] font-bold uppercase text-gray-500">Adicionar</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bottom Action Panel ("Opções") -->
                    <div class="bg-gray-200 border border-gray-400 rounded-sm p-4 relative pt-6 shadow-inner pb-10">
                         <span class="absolute -top-3 left-6 bg-gray-200 px-2 text-xs font-bold text-gray-600 border-x border-t border-gray-400 rounded-t-sm">Opções</span>
                         
                         <div class="grid grid-cols-2 gap-4 px-10">
                            <button class="flex items-center justify-center bg-[#4FA6FF] hover:bg-blue-500 text-white h-12 rounded-md text-xs font-bold shadow-lg border border-blue-600 transition-all hover:scale-[1.02] active:scale-95">
                                SOLICITAR SERVIÇOS
                            </button>
                            <button class="flex items-center justify-center bg-[#4FA6FF] hover:bg-blue-500 text-white h-12 rounded-md text-xs font-bold shadow-lg border border-blue-600 transition-all hover:scale-[1.02] active:scale-95">
                                SOLICITAR EXAME EXTERNO
                            </button>
                            <div class="flex flex-col items-center">
                                <button @click="agendarConsulta" class="w-full flex items-center justify-center bg-[#4FA6FF] hover:bg-blue-500 text-white h-12 rounded-md font-bold shadow-lg border border-blue-600 transition-all group overflow-hidden relative">
                                    <Calendar class="w-5 h-5 absolute left-3 opacity-30 group-hover:opacity-100 transition-opacity" />
                                    <span class="text-xs">AGENDAR CONSULTA</span>
                                </button>
                            </div>
                            <div class="flex flex-col items-center">
                                <button class="w-full flex items-center justify-center bg-[#4FA6FF] hover:bg-blue-500 text-white h-12 rounded-md font-bold shadow-lg border border-blue-600 transition-all group overflow-hidden relative">
                                    <Stethoscope class="w-5 h-5 absolute left-3 opacity-30 group-hover:opacity-100 transition-opacity" />
                                    <span class="text-xs">ENVIAR PARA TRIAGEM</span>
                                </button>
                            </div>
                         </div>
                    </div>
                </div>

                <!-- Right Column: Tables and Others -->
                <div class="space-y-4">
                    <div class="bg-gray-200 border border-gray-400 rounded-sm p-4 relative pt-6 shadow-inner flex flex-col h-full min-h-[600px]">
                        <span class="absolute -top-3 left-6 bg-gray-200 px-2 text-xs font-bold text-gray-600 border-x border-t border-gray-400 rounded-t-sm">Outros Dados</span>
                        
                        <!-- Filters -->
                        <div class="flex items-center space-x-2 mb-4">
                            <button class="bg-gray-100 border border-gray-300 px-3 py-1 text-[10px] font-bold rounded flex items-center hover:bg-blue-50">
                                <RotateCcw class="w-3 h-3 mr-1" /> Atualizar
                            </button>
                            <div class="flex items-center space-x-1 ml-auto">
                                <span class="text-[10px] font-bold">De:</span>
                                <input type="date" value="2024-04-24" class="text-[10px] border border-gray-300 p-1 rounded" />
                                <span class="text-[10px] font-bold">Até:</span>
                                <input type="date" value="2024-04-24" class="text-[10px] border border-gray-300 p-1 rounded" />
                            </div>
                        </div>

                        <!-- Main Table Section ("Consultas Marcadas") -->
                        <div class="flex flex-col flex-grow bg-white border border-gray-300 rounded-sm overflow-hidden">
                            <div class="bg-gray-100 px-4 py-1 border-b border-gray-300 text-center select-none">
                                <span class="text-[11px] font-bold">Consultas Marcadas</span>
                            </div>
                            <div class="bg-gray-50 text-[10px] text-gray-500 px-4 py-1 italic border-b border-gray-300">
                                Drag a column header here to group by that column
                            </div>
                            
                            <div class="flex-grow overflow-auto">
                                <table class="w-full text-[10px] text-left border-collapse">
                                    <thead class="bg-gray-100 sticky top-0 border-b border-gray-300">
                                        <tr>
                                            <th class="px-2 py-1 border-r border-gray-300 font-bold">Consulta</th>
                                            <th class="px-2 py-1 border-r border-gray-300 font-bold">Paciente</th>
                                            <th class="px-2 py-1 border-r border-gray-300 font-bold whitespace-nowrap">Data Agendame...</th>
                                            <th class="px-2 py-1 border-r border-gray-300 font-bold">Médico</th>
                                            <th class="px-2 py-1 border-r border-gray-300 font-bold">Situação</th>
                                            <th class="px-2 py-1 border-r border-gray-300 font-bold">Editar Marcação</th>
                                            <th class="px-2 py-1 font-bold">Relatório Medico</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="agenda in props.agendamentos" :key="agenda.Id" class="border-b border-gray-100 hover:bg-blue-50/50">
                                            <td class="px-2 py-1.5 border-r border-gray-200">{{ agenda.Consulta }}</td>
                                            <td class="px-2 py-1.5 border-r border-gray-200 uppercase font-bold">{{ agenda.PacienteNome }}</td>
                                            <td class="px-2 py-1.5 border-r border-gray-200">{{ agenda.DataAgendamento }}</td>
                                            <td class="px-2 py-1.5 border-r border-gray-200">{{ agenda.Medico || 'N/A' }}</td>
                                            <td class="px-2 py-1.5 border-r border-gray-200">
                                                <span :class="{
                                                    'bg-blue-100 text-blue-700': agenda.Situacao === 'Triagem',
                                                    'bg-green-100 text-green-700': agenda.Situacao === 'Finalizado',
                                                    'bg-yellow-100 text-yellow-700': agenda.Situacao === 'Agendada',
                                                }" class="px-2 py-0.5 rounded-full text-[9px] font-bold uppercase">
                                                    {{ agenda.Situacao }}
                                                </span>
                                            </td>
                                            <td class="px-2 py-1.5 border-r border-gray-200 text-center">
                                                <button class="text-blue-600 hover:underline">Editar</button>
                                            </td>
                                            <td class="px-2 py-1.5 text-center">
                                                <button class="text-gray-400"><ClipboardList class="w-4 h-4 mx-auto" /></button>
                                            </td>
                                        </tr>
                                        <tr v-if="props.agendamentos.length === 0">
                                             <td colspan="7" class="px-2 py-10 text-center text-gray-400 italic">Nenhuma consulta encontrada para hoje.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Bottom Tables -->
                        <div class="grid grid-cols-2 gap-2 mt-4">
                            <!-- Table 2 -->
                            <div class="flex flex-col h-48 bg-white border border-gray-300">
                                <div class="bg-gray-100 text-center py-1 border-b border-gray-300">
                                    <span class="text-[10px] font-bold uppercase">Exames Solicitados Por Pagar</span>
                                </div>
                                <div class="flex-grow overflow-auto">
                                    <table class="w-full text-[9px]">
                                        <thead class="bg-gray-50 border-b border-gray-200 sticky top-0">
                                            <tr>
                                                <th class="px-1 border-r">AGENDA</th>
                                                <th class="px-1 border-r">PROCE...</th>
                                                <th class="px-1 border-r">PACIENTE</th>
                                                <th class="px-1 border-r">DATA_</th>
                                                <th class="px-1 border-r">QTD</th>
                                                <th class="px-1">PAGA...</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="i in 5" :key="i" class="border-b border-gray-50">
                                                <td colspan="6" class="p-1">&nbsp;</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <!-- Table 3 -->
                            <div class="flex flex-col h-48 bg-white border border-gray-300">
                                <div class="bg-gray-100 text-center py-1 border-b border-gray-300">
                                    <span class="text-[10px] font-bold uppercase">Área de Internamento</span>
                                </div>
                                <div class="flex-grow overflow-auto">
                                    <table class="w-full text-[9px]">
                                        <thead class="bg-gray-50 border-b border-gray-200 sticky top-0">
                                            <tr>
                                                <th class="px-1 border-r">Paciente</th>
                                                <th class="px-1 border-r">Consulta</th>
                                                <th class="px-1">Data Interna...</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="i in 5" :key="i" class="border-b border-gray-50">
                                                <td colspan="3" class="p-1">&nbsp;</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Page Footer Controls -->
            <div class="bg-gray-300 border-t border-gray-400 p-2 flex items-center justify-between text-[10px] font-bold text-gray-600">
                <div class="flex items-center space-x-4">
                    <span>CAPACIDADE: 100/100</span>
                    <span>REGISTOS: 0</span>
                </div>
                <div class="flex items-center space-x-2">
                    <MousePointer2 class="w-3 h-3" />
                    <span>SISTEMA ATIVO</span>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>

<style scoped>
input[type="date"]::-webkit-calendar-picker-indicator {
    padding-left: 20px;
    background-image: url('data:image/svg+xml;utf8,<svg fill="black" height="24" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M7 10l5 5 5-5z"/><path d="M0 0h24v24H0z" fill="none"/></svg>');
    background-repeat: no-repeat;
    cursor: pointer;
}

/* Scrollbar styling for tables */
::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}
::-webkit-scrollbar-track {
    background: #f1f1f1;
}
::-webkit-scrollbar-thumb {
    background: #ccc;
    border-radius: 10px;
}
::-webkit-scrollbar-thumb:hover {
    background: #999;
}
</style>
