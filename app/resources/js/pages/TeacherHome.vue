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
              <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                Ações
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

              <td class="px-5 py-4">
                <div class="flex justify-end gap-2">
                  <button type="button"
                    class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50"
                    @click="viewExam(exam)">
                    Ver
                  </button>

                  <button type="button"
                    class="rounded-lg border border-red-200 px-3 py-2 text-xs font-semibold text-red-700 transition hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-60"
                    :disabled="deletingExamId === exam.id" @click="deleteExam(exam)">
                    {{ deletingExamId === exam.id ? 'Excluindo...' : 'Excluir' }}
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div v-if="selectedExamLoading"
      class="mt-8 rounded-xl border border-slate-200 bg-slate-50 p-5 text-sm text-slate-600">
      Carregando detalhes da prova...
    </div>

    <div v-else-if="selectedExamErrorMessage"
      class="mt-8 rounded-xl border border-red-200 bg-red-50 p-5 text-sm text-red-700">
      {{ selectedExamErrorMessage }}
    </div>

    <div v-else-if="selectedExam" class="mt-8 rounded-2xl border border-slate-200 bg-slate-50 p-5">
      <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
        <div>
          <p class="text-sm font-semibold uppercase tracking-wide text-indigo-600">
            Detalhes da prova
          </p>

          <h3 class="mt-2 text-2xl font-bold text-slate-900">
            {{ selectedExam.title }}
          </h3>

          <p class="mt-2 text-sm text-slate-600">
            {{ selectedExam.description || 'Sem descrição.' }}
          </p>
        </div>

        <button type="button"
          class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-white"
          @click="clearSelectedExam">
          Fechar
        </button>
      </div>

      <div class="mt-6 space-y-4">
        <article v-for="(question, questionIndex) in selectedExam.questions" :key="question.id"
          class="rounded-xl border border-slate-200 bg-white p-5">
          <h4 class="font-semibold text-slate-900">
            {{ questionIndex + 1 }}. {{ question.statement }}
          </h4>

          <div class="mt-4 space-y-2">
            <div v-for="alternative in question.alternatives" :key="alternative.id"
              class="flex items-start justify-between gap-3 rounded-xl border border-slate-200 p-3 text-sm"
              :class="alternative.is_correct ? 'border-emerald-200 bg-emerald-50' : 'bg-white'">
              <span class="text-slate-700">
                {{ alternative.text }}
              </span>

              <span v-if="alternative.is_correct"
                class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">
                Correta
              </span>
            </div>
          </div>
        </article>
      </div>
    </div>

    <section class="mt-8">
      <div class="mb-4 flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
        <div>
          <h3 class="text-xl font-bold text-slate-900">
            Ranking de tentativas
          </h3>

          <p class="mt-1 text-sm text-slate-600">
            Melhores desempenhos registrados nas provas.
          </p>
        </div>
      </div>

      <div v-if="rankingLoading" class="rounded-xl border border-slate-200 bg-slate-50 p-5 text-sm text-slate-600">
        Carregando ranking...
      </div>

      <div v-else-if="rankingErrorMessage" class="rounded-xl border border-red-200 bg-red-50 p-5 text-sm text-red-700">
        {{ rankingErrorMessage }}
      </div>

      <div v-else-if="ranking.length === 0"
        class="rounded-xl border border-slate-200 bg-slate-50 p-5 text-sm text-slate-600">
        Nenhuma tentativa registrada até o momento.
      </div>

      <div v-else class="overflow-hidden rounded-2xl border border-slate-200">
        <table class="min-w-full divide-y divide-slate-200">
          <thead class="bg-slate-50">
            <tr>
              <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                Posição
              </th>
              <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                Aluno
              </th>
              <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                Prova
              </th>
              <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                Pontuação
              </th>
              <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                Percentual
              </th>
              <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                Enviado em
              </th>
            </tr>
          </thead>

          <tbody class="divide-y divide-slate-200 bg-white">
            <tr v-for="(attempt, index) in ranking" :key="attempt.id" class="transition hover:bg-slate-50">
              <td class="px-5 py-4 text-sm font-semibold text-slate-900">
                #{{ index + 1 }}
              </td>

              <td class="px-5 py-4">
                <p class="font-semibold text-slate-900">
                  {{ attempt.student_name || 'Aluno sem nome' }}
                </p>

                <p class="mt-1 text-sm text-slate-500">
                  {{ attempt.student_identifier }}
                </p>
              </td>

              <td class="px-5 py-4 text-sm text-slate-600">
                {{ attempt.exam?.title || '-' }}
              </td>

              <td class="px-5 py-4 text-sm text-slate-600">
                {{ attempt.score }} / {{ attempt.total_questions }}
              </td>

              <td class="px-5 py-4">
                <span class="inline-flex rounded-full bg-indigo-100 px-3 py-1 text-xs font-semibold text-indigo-700">
                  {{ attempt.percentage }}%
                </span>
              </td>

              <td class="px-5 py-4 text-sm text-slate-600">
                {{ formatDate(attempt.submitted_at) }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>
  </section>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { api } from '../services/api';

const exams = ref([]);
const loading = ref(false);
const errorMessage = ref('');

const ranking = ref([]);
const rankingLoading = ref(false);
const rankingErrorMessage = ref('');

const selectedExam = ref(null);
const selectedExamLoading = ref(false);
const selectedExamErrorMessage = ref('');

const deletingExamId = ref(null);

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

async function loadRanking() {
  rankingLoading.value = true;
  rankingErrorMessage.value = '';

  try {
    const response = await api.get('/dashboard/ranking?per_page=10');

    ranking.value = response.data || [];
  } catch (error) {
    rankingErrorMessage.value = error.message || 'Não foi possível carregar o ranking.';
  } finally {
    rankingLoading.value = false;
  }
}

async function viewExam(exam) {
  selectedExam.value = null;
  selectedExamLoading.value = true;
  selectedExamErrorMessage.value = '';

  try {
    const response = await api.get(`/exams/${exam.id}`);

    selectedExam.value = response.data;
  } catch (error) {
    selectedExamErrorMessage.value = error.message || 'Não foi possível carregar os detalhes da prova.';
  } finally {
    selectedExamLoading.value = false;
  }
}

async function deleteExam(exam) {
  const confirmed = window.confirm(`Deseja realmente excluir a prova "${exam.title}"?`);

  if (!confirmed) {
    return;
  }

  deletingExamId.value = exam.id;
  errorMessage.value = '';

  try {
    await api.delete(`/exams/${exam.id}`);

    if (selectedExam.value?.id === exam.id) {
      clearSelectedExam();
    }

    await loadDashboard();
    await loadExams();
    await loadRanking();
  } catch (error) {
    errorMessage.value = error.message || 'Não foi possível excluir a prova.';
  } finally {
    deletingExamId.value = null;
  }
}

function clearSelectedExam() {
  selectedExam.value = null;
  selectedExamErrorMessage.value = '';
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
  loadRanking();
});
</script>