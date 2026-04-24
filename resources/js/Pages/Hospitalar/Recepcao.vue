<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import axios from 'axios';
import { 
    Search, UserPlus, Plus, ClipboardList, Stethoscope, 
    Calendar, MousePointer2, RotateCcw, FileText, Activity, 
    CreditCard, Users
} from 'lucide-vue-next';

const props = defineProps({
    medicos: Array,
    consultas: Array,
    seguradoras: Array,
    agendamentos: Array
});

const form = useForm({
    IdPaciente: '',
    nome: '',
    filiacao_pai: '',
    filiacao_mae: '',
    data_nascimento: new Date().toISOString().split('T')[0],
    idade: 0,
    telefone: '',
    sexo: 'MASCULINO',
    endereco: '',
    tipo_paciente: 'Particular',
    IdSeguradora: '',
    IdConsulta: '',
    IdMedico: '',
    DataAgendamento: new Date().toISOString().split('T')[0],
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
            form.IdPaciente = p.Codigo;
            form.nome = p.Nome;
            form.telefone = p.Telefone || '';
            form.endereco = p.Endereco || '';
            form.filiacao_pai = p.Pai || '';
            form.filiacao_mae = p.Mae || '';
            form.sexo = p.Genero || 'MASCULINO';
            
            // Calculate age if birthdate exists
            if (p.DataNascimento) {
                const birth = new Date(p.DataNascimento);
                const today = new Date();
                let age = today.getFullYear() - birth.getFullYear();
                const m = today.getMonth() - birth.getMonth();
                if (m < 0 || (m === 0 && today.getDate() < birth.getDate())) {
                    age--;
                }
                form.idade = age;
                form.data_nascimento = p.DataNascimento;
            }
        } else {
            alert('Paciente não encontrado. Certifique-se que o paciente está registado.');
        }
    } catch (error) {
        console.error(error);
    } finally {
        searchLoading.value = false;
    }
};

const agendarConsulta = () => {
    if (!form.IdPaciente) {
        alert('Selecione um paciente primeiro através da busca.');
        return;
    }

    form.post(route('hospitalar.recepcao.store'), {
        onSuccess: () => {
            alert('Admissão realizada com sucesso!');
            form.reset();
            form.DataAgendamento = new Date().toISOString().split('T')[0];
            searchTerm.value = '';
        },
        onError: (err) => {
            console.error(err);
            alert('Erro ao realizar agendamento.');
        }
    });
};

const limparForm = () => {
    form.reset();
    searchTerm.value = '';
    form.DataAgendamento = new Date().toISOString().split('T')[0];
};
</script>

<template>
    <Head title="Recepção - Marcação de Consulta" />

    <DashboardLayout>
        
        <!-- Header Modernizado -->
        <div class="mb-6 bg-gradient-to-r from-[#0f172a] to-[#1e3a8a] rounded-2xl p-6 text-white shadow-lg relative overflow-hidden flex justify-between items-center">
            <div class="absolute inset-0 bg-grid-white/[0.05] bg-[size:20px_20px]"></div>
            <div class="relative z-10">
                <h1 class="text-2xl font-black tracking-tight flex items-center space-x-3">
                    <Activity class="w-8 h-8 text-blue-400" />
                    <span>MARCAÇÃO DE CONSULTA</span>
                </h1>
                <p class="text-blue-200 text-sm font-medium tracking-wide mt-1">Recepção Hospitalar AGEOS</p>
            </div>
            <div class="relative z-10 flex items-center space-x-4">
                <div class="text-right border-r border-white/10 pr-4">
                    <p class="text-[10px] font-black uppercase text-blue-300">Data de Hoje</p>
                    <p class="text-sm font-bold">{{ new Date().toLocaleDateString('pt-PT') }}</p>
                </div>
                <div class="bg-white/10 p-2 rounded-xl backdrop-blur-md border border-white/10">
                    <Calendar class="w-6 h-6" />
                </div>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 pb-6">
            
            <!-- Left Column: Patient Data (7 cols) -->
            <div class="lg:col-span-7 space-y-6">
                
                <!-- Patient Form Card -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center justify-between mb-6 pb-4 border-b border-gray-50">
                        <h3 class="text-sm font-black uppercase tracking-widest text-gray-800 flex items-center">
                            <Users class="w-4 h-4 mr-2 text-blue-500" /> DADOS DO PACIENTE
                        </h3>
                        <!-- Search Tools -->
                        <div class="flex items-center space-x-3">
                            <div class="relative">
                                <Search class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
                                <input v-model="searchTerm" @keyup.enter="buscarPaciente" placeholder="Código (Ex: PC001), Nome ou NIF..." class="pl-9 pr-4 py-2 border-gray-200 rounded-lg text-xs w-64 focus:ring-blue-500 focus:border-blue-500 transition-shadow bg-gray-50 hover:bg-white" />
                            </div>
                            <button @click="buscarPaciente" :disabled="searchLoading" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-xs font-bold transition-colors disabled:opacity-50 flex items-center shadow-md shadow-blue-500/20">
                                <span v-if="searchLoading" class="w-3 h-3 mr-2 animate-spin rounded-full border-2 border-white border-t-transparent"></span>
                                BUSCAR
                            </button>
                            <button @click="limparForm" class="bg-gray-100 hover:bg-gray-200 text-gray-600 px-4 py-2 rounded-lg text-xs font-bold transition-colors">
                                <RotateCcw class="w-3.5 h-3.5" />
                            </button>
                        </div>
                    </div>

                    <form @submit.prevent="agendarConsulta" class="space-y-4">
                        <!-- Row 1 -->
                        <div class="grid grid-cols-12 gap-4 items-center">
                            <div class="col-span-2 text-xs font-bold text-gray-600 uppercase tracking-wide">Identificação</div>
                            <div class="col-span-2 relative">
                                <span class="absolute -top-2 left-3 bg-white px-1 text-[9px] font-bold text-gray-400">Código</span>
                                <input v-model="form.IdPaciente" readonly class="w-full border-gray-200 rounded-lg px-3 py-2 text-xs bg-gray-50 font-bold text-blue-600" />
                            </div>
                            <div class="col-span-8 relative">
                                <span class="absolute -top-2 left-3 bg-white px-1 text-[9px] font-bold text-gray-400">Nome Completo</span>
                                <input v-model="form.nome" readonly class="w-full border-gray-200 rounded-lg px-3 py-2 text-xs bg-gray-50 font-bold" />
                            </div>
                        </div>

                        <!-- Row 2 -->
                        <div class="grid grid-cols-12 gap-4 items-center">
                            <div class="col-span-2 text-xs font-bold text-gray-600 uppercase tracking-wide">Filiação</div>
                            <div class="col-span-5 relative">
                                <span class="absolute -top-2 left-3 bg-white px-1 text-[9px] font-bold text-gray-400">Pai</span>
                                <input v-model="form.filiacao_pai" class="w-full border-gray-200 rounded-lg px-3 py-2 text-xs focus:ring-blue-500 focus:border-blue-500" />
                            </div>
                            <div class="col-span-5 relative">
                                <span class="absolute -top-2 left-3 bg-white px-1 text-[9px] font-bold text-gray-400">Mãe</span>
                                <input v-model="form.filiacao_mae" class="w-full border-gray-200 rounded-lg px-3 py-2 text-xs focus:ring-blue-500 focus:border-blue-500" />
                            </div>
                        </div>

                        <!-- Row 3 -->
                        <div class="grid grid-cols-12 gap-4 items-center">
                            <div class="col-span-2 text-xs font-bold text-gray-600 uppercase tracking-wide">Nascimento</div>
                            <div class="col-span-4">
                                <input type="date" v-model="form.data_nascimento" class="w-full border-gray-200 rounded-lg px-3 py-2 text-xs focus:ring-blue-500 focus:border-blue-500" />
                            </div>
                            <div class="col-span-1 text-xs font-bold text-gray-600 uppercase tracking-wide pl-2">Idade</div>
                            <div class="col-span-2 flex items-center space-x-2">
                                <input v-model="form.idade" class="w-full border-gray-200 rounded-lg px-3 py-2 text-xs text-center focus:ring-blue-500 focus:border-blue-500" />
                                <span class="text-xs font-bold text-gray-400">Anos</span>
                            </div>
                            <div class="col-span-1 text-xs font-bold text-gray-600 uppercase tracking-wide pl-2">Sexo</div>
                            <div class="col-span-2">
                                <select v-model="form.sexo" class="w-full border-gray-200 rounded-lg px-2 py-2 text-xs focus:ring-blue-500 focus:border-blue-500">
                                    <option value="MASCULINO">MASCULINO</option>
                                    <option value="FEMININO">FEMININO</option>
                                </select>
                            </div>
                        </div>

                        <!-- Row 4 -->
                        <div class="grid grid-cols-12 gap-4 items-center">
                            <div class="col-span-2 text-xs font-bold text-gray-600 uppercase tracking-wide">Endereço</div>
                            <div class="col-span-10 flex space-x-2">
                                <input v-model="form.endereco" class="flex-grow border-gray-200 rounded-lg px-3 py-2 text-xs focus:ring-blue-500 focus:border-blue-500" placeholder="Morada do paciente" />
                            </div>
                        </div>

                        <div class="grid grid-cols-12 gap-4 items-center">
                            <div class="col-span-2 text-xs font-bold text-gray-600 uppercase tracking-wide">Telefone</div>
                            <div class="col-span-4">
                                <input v-model="form.telefone" class="w-full border-gray-200 rounded-lg px-3 py-2 text-xs focus:ring-blue-500 focus:border-blue-500" placeholder="Terminal" />
                            </div>
                        </div>

                        <hr class="border-gray-100 my-4" />

                        <!-- Health/Insurance Section -->
                        <div class="grid grid-cols-12 gap-4 items-center">
                            <div class="col-span-2 text-xs font-bold text-gray-600 uppercase tracking-wide">Tipo</div>
                            <div class="col-span-10 flex space-x-6">
                                <label class="flex items-center space-x-2 cursor-pointer group">
                                    <input type="radio" value="Particular" v-model="form.tipo_paciente" class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500" />
                                    <span class="text-sm font-medium text-gray-700 group-hover:text-blue-600 transition-colors">Particular</span>
                                </label>
                                <label class="flex items-center space-x-2 cursor-pointer group">
                                    <input type="radio" value="Assegurado" v-model="form.tipo_paciente" class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500" />
                                    <span class="text-sm font-medium text-gray-700 group-hover:text-blue-600 transition-colors">Assegurado</span>
                                </label>
                            </div>
                        </div>

                        <div class="grid grid-cols-12 gap-4 items-center">
                            <div class="col-span-2 text-xs font-bold text-gray-600 uppercase tracking-wide">Seguradora</div>
                            <div class="col-span-10 flex space-x-2">
                                <select v-model="form.IdSeguradora" :disabled="form.tipo_paciente !== 'Assegurado'" class="flex-grow border-gray-200 rounded-lg px-3 py-2 text-xs focus:ring-blue-500 focus:border-blue-500 disabled:bg-gray-50 disabled:text-gray-400">
                                    <option value="">Selecione uma seguradora...</option>
                                    <option v-for="s in props.seguradoras" :key="s.Id" :value="s.Id">{{ s.Nome }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-12 gap-4 items-center">
                            <div class="col-span-2 text-xs font-bold text-gray-600 uppercase tracking-wide">Consulta</div>
                            <div class="col-span-10 flex space-x-2">
                                <select v-model="form.IdConsulta" class="flex-grow border-gray-200 rounded-lg px-3 py-2 text-xs focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Selecione o tipo de consulta...</option>
                                    <option v-for="c in props.consultas" :key="c.Id" :value="c.Id">{{ c.Descricao }} - {{ c.Valor }} KZ</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-12 gap-4 items-center">
                            <div class="col-span-2 text-xs font-bold text-gray-600 uppercase tracking-wide">Médico</div>
                            <div class="col-span-10 flex space-x-2">
                                <select v-model="form.IdMedico" class="flex-grow border-gray-200 rounded-lg px-3 py-2 text-xs focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Selecione o médico...</option>
                                    <option v-for="m in props.medicos" :key="m.Id" :value="m.Id">{{ m.Nome }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="mt-8 flex justify-end">
                            <button type="submit" :disabled="form.processing" class="bg-gray-800 hover:bg-gray-900 text-white px-10 py-3 rounded-xl font-black text-sm transition-all shadow-lg flex items-center">
                                <span v-if="form.processing" class="w-4 h-4 mr-2 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                                ADMITIR PACIENTE
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Right Column: Queue & Stats (5 cols) -->
            <div class="lg:col-span-5 space-y-6">
                
                <!-- Main Table Section ("Consultas Marcadas") -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 flex flex-col flex-grow min-h-[400px]">
                    <div class="p-5 border-b border-gray-50 flex items-center justify-between bg-gray-50/50 rounded-t-2xl">
                        <h3 class="text-sm font-black uppercase tracking-widest text-gray-800 flex items-center">
                            <Calendar class="w-4 h-4 mr-2 text-blue-500" /> CONSULTAS DO DIA
                        </h3>
                        <div class="flex items-center space-x-2">
                            <input type="date" class="border-gray-200 rounded-lg text-xs py-1.5 focus:ring-blue-500" />
                            <button class="p-1.5 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 text-gray-500 transition-colors">
                                <RotateCcw class="w-3.5 h-3.5" />
                            </button>
                        </div>
                    </div>
                    
                    <div class="flex-grow overflow-auto p-4 custom-scrollbar">
                        <div v-if="props.agendamentos.length > 0" class="space-y-3">
                            <!-- Card Base Table items -->
                            <div v-for="agenda in props.agendamentos" :key="agenda.Id" class="p-3 bg-white border border-gray-100 rounded-xl shadow-sm hover:shadow-md transition-shadow flex flex-col relative overflow-hidden group">
                                <div class="absolute left-0 top-0 bottom-0 w-1 bg-blue-500"></div>
                                <div class="flex justify-between items-start pl-3">
                                    <div>
                                        <p class="text-xs font-black text-gray-800 uppercase">{{ agenda.PacienteNome }}</p>
                                        <p class="text-[10px] text-gray-500 font-medium mt-0.5">{{ agenda.Consulta }}</p>
                                    </div>
                                    <span :class="{
                                        'bg-blue-50 text-blue-700 border-blue-200': agenda.Situacao === 'Triagem',
                                        'bg-green-50 text-green-700 border-green-200': agenda.Situacao === 'Finalizado',
                                        'bg-yellow-50 text-yellow-700 border-yellow-200': agenda.Situacao === 'Agendada',
                                    }" class="px-2 py-1 rounded-md border text-[9px] font-bold uppercase tracking-wider">
                                        {{ agenda.Situacao }}
                                    </span>
                                </div>
                                <div class="mt-3 pl-3 pt-3 border-t border-gray-50 flex justify-between items-center">
                                    <span class="text-[10px] text-gray-400 font-medium">{{ agenda.DataAgendamento }}</span>
                                    <div class="flex space-x-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <button class="text-[10px] font-bold text-blue-600 hover:text-blue-800 transition-colors">Editar</button>
                                        <span class="text-gray-300">|</span>
                                        <button class="text-[10px] font-bold text-gray-500 hover:text-gray-800 transition-colors flex items-center">
                                            <ClipboardList class="w-3 h-3 mr-1" /> Relatório
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="h-full flex flex-col items-center justify-center py-12 opacity-60">
                            <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mb-3">
                                <Calendar class="w-8 h-8 text-gray-300" />
                            </div>
                            <p class="text-sm font-medium text-gray-500">Sem consultas para hoje</p>
                            <p class="text-[10px] text-gray-400 mt-1">Os agendamentos aparecerão aqui</p>
                        </div>
                    </div>
                </div>

                <!-- Bottom Status Tables -->
                <div class="grid grid-cols-2 gap-6">
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 flex flex-col h-48">
                        <div class="p-3 border-b border-gray-50 bg-gray-50/50 rounded-t-2xl text-center">
                            <span class="text-[10px] font-black uppercase tracking-widest text-gray-600">Exames por Pagar</span>
                        </div>
                        <div class="flex-grow flex items-center justify-center">
                            <p class="text-[10px] text-gray-400 font-medium italic">Nenhum exame pendente</p>
                        </div>
                    </div>
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 flex flex-col h-48">
                        <div class="p-3 border-b border-gray-50 bg-gray-50/50 rounded-t-2xl text-center">
                            <span class="text-[10px] font-black uppercase tracking-widest text-gray-600">Internamento</span>
                        </div>
                        <div class="flex-grow flex items-center justify-center">
                            <p class="text-[10px] text-gray-400 font-medium italic">Nenhum internamento</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </DashboardLayout>
</template>

<style scoped>
/* Modern Scrollbar */
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
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

/* Background Pattern */
.bg-grid-white\/\[0\.05\] {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32' width='32' height='32' fill='none' stroke='%23ffffff' stroke-opacity='0.05'%3E%3Cpath d='M0 .5H31.5V32'/%3E%3C/svg%3E");
}
</style>
