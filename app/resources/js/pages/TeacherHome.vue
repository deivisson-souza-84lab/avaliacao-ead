<template>
  <section>
    <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
      <div>
        <p class="text-sm font-semibold uppercase tracking-wide text-indigo-600">
          Área do Professor
        </p>

        <h2 class="mt-2 text-3xl font-bold">
          Gerenciamento de provas
        </h2>

        <p class="mt-3 text-slate-600">
          Consulte as provas cadastradas e acompanhe a estrutura de questões.
        </p>
      </div>

      <button type="button"
        class="rounded-xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700">
        Nova prova
      </button>
    </div>

    <div class="mt-8 grid gap-4 md:grid-cols-3">
      <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <p class="text-sm font-medium text-slate-500">
          Média geral
        </p>

        <p class="mt-2 text-3xl font-bold text-slate-900">
          {{ dashboard.average_score }}%
        </p>
      </article>

      <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <p class="text-sm font-medium text-slate-500">
          Melhor pontuação
        </p>

        <p class="mt-2 text-3xl font-bold text-slate-900">
          {{ dashboard.best_score }}%
        </p>
      </article>

      <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <p class="text-sm font-medium text-slate-500">
          Tentativas
        </p>

        <p class="mt-2 text-3xl font-bold text-slate-900">
          {{ dashboard.total_attempts }}
        </p>
      </article>
    </div>

    <div class="mt-8">
      <div v-if="loading" class="rounded-xl border border-slate-200 bg-slate-50 p-5 text-sm text-slate-600">
        Carregando provas...
      </div>

      <div v-else-if="errorMessage" class="rounded-xl border border-red-200 bg-red-50 p-5 text-sm text-red-700">
        {{ errorMessage }}
      </div>

      <div v-else-if="exams.length === 0"
        class="rounded-xl border border-slate-200 bg-slate-50 p-5 text-sm text-slate-600">
        Nenhuma prova cadastrada até o momento.
      </div>

      <div v-else class="overflow-hidden rounded-2xl border border-slate-200">
        <table class="min-w-full divide-y divide-slate-200">
          <thead class="bg-slate-50">
            <tr>
              <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                Prova
              </th>
              <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                Status
              </th>
              <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                Criada em
              </th>
            </tr>
          </thead>

          <tbody class="divide-y divide-slate-200 bg-white">
            <tr v-for="exam in exams" :key="exam.id" class="transition hover:bg-slate-50">
              <td class="px-5 py-4">
                <p class="font-semibold text-slate-900">
                  {{ exam.title }}
                </p>

                <p class="mt-1 text-sm text-slate-500">
                  {{ exam.description || 'Sem descrição.' }}
                </p>
              </td>

              <td class="px-5 py-4">
                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold" :class="exam.is_available
                  ? 'bg-emerald-100 text-emerald-700'
                  : 'bg-slate-100 text-slate-600'">
                  {{ exam.is_available ? 'Disponível' : 'Indisponível' }}
                </span>
              </td>

              <td class="px-5 py-4 text-sm text-slate-600">
                {{ formatDate(exam.created_at) }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </section>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { api } from '../services/api';

const exams = ref([]);
const loading = ref(false);
const errorMessage = ref('');

const dashboard = ref({
  average_score: 0,
  best_score: 0,
  total_attempts: 0,
});

async function loadDashboard() {
  try {
    const response = await api.get('/dashboard');

    dashboard.value = response.data || {
      average_score: 0,
      best_score: 0,
      total_attempts: 0,
    };
  } catch (error) {
    console.error(error);
  }
}

async function loadExams() {
  loading.value = true;
  errorMessage.value = '';

  try {
    const response = await api.get('/exams');

    exams.value = response.data || [];
  } catch (error) {
    errorMessage.value = error.message || 'Não foi possível carregar as provas.';
  } finally {
    loading.value = false;
  }
}

function formatDate(value) {
  if (!value) {
    return '-';
  }

  const date = new Date(value);

  const formattedDate = new Intl.DateTimeFormat('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  }).format(date);

  const formattedTime = new Intl.DateTimeFormat('pt-BR', {
    hour: '2-digit',
    minute: '2-digit',
  }).format(date);

  return `${formattedDate} - ${formattedTime}`;
}

onMounted(() => {
  loadDashboard();
  loadExams();
});
</script>