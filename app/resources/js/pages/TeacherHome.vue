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
        class="rounded-xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700"
        @click="openCreateForm">
        Nova prova
      </button>
    </div>

    <div v-if="showCreateForm" class="mt-8 rounded-2xl border border-slate-200 bg-slate-50 p-5">
      <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
        <div>
          <p class="text-sm font-semibold uppercase tracking-wide text-indigo-600">
            Nova prova
          </p>

          <h3 class="mt-2 text-2xl font-bold text-slate-900">
            Cadastro de prova
          </h3>

          <p class="mt-2 text-sm text-slate-600">
            Informe os dados da prova, suas questões e alternativas.
          </p>
        </div>

        <button type="button"
          class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-white"
          @click="closeCreateForm">
          Cancelar
        </button>
      </div>

      <form class="mt-6 space-y-6" @submit.prevent="submitCreateExam">
        <div class="grid gap-4 md:grid-cols-2">
          <div>
            <label class="text-sm font-medium text-slate-700" for="exam-title">
              Título
            </label>

            <input id="exam-title" v-model="examForm.title" type="text"
              class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
              placeholder="Ex.: Fundamentos de PHP" :disabled="creatingExam">

            <p v-if="firstCreateError('title')" class="mt-1 text-sm text-red-600">
              {{ firstCreateError('title') }}
            </p>
          </div>

          <div>
            <label class="text-sm font-medium text-slate-700" for="exam-status">
              Status
            </label>

            <select id="exam-status" v-model="examForm.is_available"
              class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
              :disabled="creatingExam">
              <option :value="true">Disponível</option>
              <option :value="false">Indisponível</option>
            </select>
          </div>
        </div>

        <div>
          <label class="text-sm font-medium text-slate-700" for="exam-description">
            Descrição
          </label>

          <textarea id="exam-description" v-model="examForm.description" rows="3"
            class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
            placeholder="Descreva brevemente a prova." :disabled="creatingExam" />

          <p v-if="firstCreateError('description')" class="mt-1 text-sm text-red-600">
            {{ firstCreateError('description') }}
          </p>
        </div>

        <div>
          <div class="flex items-center justify-between gap-3">
            <div>
              <h4 class="font-semibold text-slate-900">
                Questões
              </h4>

              <p class="text-sm text-slate-600">
                Cada questão precisa ter pelo menos duas alternativas e exatamente uma correta.
              </p>
            </div>

            <button type="button"
              class="rounded-xl border border-indigo-200 px-4 py-2 text-sm font-semibold text-indigo-700 transition hover:bg-indigo-50"
              :disabled="creatingExam" @click="addQuestion">
              Adicionar questão
            </button>
          </div>

          <p v-if="firstCreateError('questions')" class="mt-2 text-sm text-red-600">
            {{ firstCreateError('questions') }}
          </p>

          <div class="mt-4 space-y-4">
            <article v-for="(question, questionIndex) in examForm.questions" :key="questionIndex"
              class="rounded-xl border border-slate-200 bg-white p-5">
              <div class="flex items-start justify-between gap-3">
                <div class="flex-1">
                  <label class="text-sm font-medium text-slate-700">
                    Questão {{ questionIndex + 1 }}
                  </label>

                  <textarea v-model="question.statement" rows="2"
                    class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                    placeholder="Digite o enunciado da questão." :disabled="creatingExam" />

                  <p v-if="firstCreateError(`questions.${questionIndex}.statement`)" class="mt-1 text-sm text-red-600">
                    {{ firstCreateError(`questions.${questionIndex}.statement`) }}
                  </p>
                </div>

                <button type="button"
                  class="rounded-xl border border-red-200 px-3 py-2 text-xs font-semibold text-red-700 transition hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-50"
                  :disabled="creatingExam || examForm.questions.length === 1" @click="removeQuestion(questionIndex)">
                  Remover
                </button>
              </div>

              <div class="mt-4">
                <div class="flex items-center justify-between gap-3">
                  <h5 class="text-sm font-semibold text-slate-900">
                    Alternativas
                  </h5>

                  <button type="button"
                    class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50"
                    :disabled="creatingExam" @click="addAlternative(questionIndex)">
                    Adicionar alternativa
                  </button>
                </div>

                <p v-if="firstCreateError(`questions.${questionIndex}.alternatives`)" class="mt-2 text-sm text-red-600">
                  {{ firstCreateError(`questions.${questionIndex}.alternatives`) }}
                </p>

                <div class="mt-3 space-y-3">
                  <div v-for="(alternative, alternativeIndex) in question.alternatives" :key="alternativeIndex"
                    class="grid gap-3 rounded-xl border border-slate-200 p-3 md:grid-cols-[1fr_auto_auto]">
                    <div>
                      <input v-model="alternative.text" type="text"
                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                        :placeholder="`Alternativa ${alternativeIndex + 1}`" :disabled="creatingExam">

                      <p v-if="firstCreateError(`questions.${questionIndex}.alternatives.${alternativeIndex}.text`)"
                        class="mt-1 text-sm text-red-600">
                        {{ firstCreateError(`questions.${questionIndex}.alternatives.${alternativeIndex}.text`) }}
                      </p>
                    </div>

                    <label class="flex items-center gap-2 text-sm font-medium text-slate-700">
                      <input type="radio" :name="`correct-${questionIndex}`" :checked="alternative.is_correct"
                        :disabled="creatingExam" @change="markCorrectAlternative(questionIndex, alternativeIndex)">
                      Correta
                    </label>

                    <button type="button"
                      class="rounded-lg border border-red-200 px-3 py-2 text-xs font-semibold text-red-700 transition hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-50"
                      :disabled="creatingExam || question.alternatives.length === 2"
                      @click="removeAlternative(questionIndex, alternativeIndex)">
                      Remover
                    </button>
                  </div>
                </div>
              </div>
            </article>
          </div>
        </div>

        <div v-if="createErrorMessage" class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
          {{ createErrorMessage }}
        </div>

        <div class="flex justify-end gap-3">
          <button type="button"
            class="rounded-xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-white"
            :disabled="creatingExam" @click="closeCreateForm">
            Cancelar
          </button>

          <button type="submit"
            class="rounded-xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:bg-slate-300"
            :disabled="creatingExam">
            {{ creatingExam ? 'Salvando...' : 'Salvar prova' }}
          </button>
        </div>
      </form>
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

const showCreateForm = ref(false);
const creatingExam = ref(false);
const createErrorMessage = ref('');
const createErrors = ref({});

const examForm = ref(createEmptyExamForm());

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

async function submitCreateExam() {
  creatingExam.value = true;
  createErrorMessage.value = '';
  createErrors.value = {};

  try {
    await api.post('/exams', examForm.value);

    closeCreateForm();

    await loadDashboard();
    await loadExams();
    await loadRanking();
  } catch (error) {
    createErrorMessage.value = error.message || 'Não foi possível cadastrar a prova.';
    createErrors.value = error.errors || {};
  } finally {
    creatingExam.value = false;
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

function createEmptyExamForm() {
  return {
    title: '',
    description: '',
    is_available: true,
    questions: [
      createEmptyQuestion(),
    ],
  };
}

function createEmptyQuestion() {
  return {
    statement: '',
    alternatives: [
      createEmptyAlternative(true),
      createEmptyAlternative(false),
    ],
  };
}

function createEmptyAlternative(isCorrect = false) {
  return {
    text: '',
    is_correct: isCorrect,
  };
}

function openCreateForm() {
  showCreateForm.value = true;
  createErrorMessage.value = '';
  createErrors.value = {};
  examForm.value = createEmptyExamForm();
}

function closeCreateForm() {
  showCreateForm.value = false;
  createErrorMessage.value = '';
  createErrors.value = {};
  examForm.value = createEmptyExamForm();
}

function addQuestion() {
  examForm.value.questions.push(createEmptyQuestion());
}

function removeQuestion(questionIndex) {
  if (examForm.value.questions.length === 1) {
    return;
  }

  examForm.value.questions.splice(questionIndex, 1);
}

function addAlternative(questionIndex) {
  examForm.value.questions[questionIndex].alternatives.push(createEmptyAlternative(false));
}

function removeAlternative(questionIndex, alternativeIndex) {
  const alternatives = examForm.value.questions[questionIndex].alternatives;

  if (alternatives.length === 2) {
    return;
  }

  const removedAlternative = alternatives[alternativeIndex];

  alternatives.splice(alternativeIndex, 1);

  if (removedAlternative.is_correct && alternatives.length > 0) {
    alternatives[0].is_correct = true;
  }
}

function markCorrectAlternative(questionIndex, alternativeIndex) {
  examForm.value.questions[questionIndex].alternatives = examForm.value.questions[
    questionIndex
  ].alternatives.map((alternative, currentIndex) => ({
    ...alternative,
    is_correct: currentIndex === alternativeIndex,
  }));
}

function firstCreateError(field) {
  return createErrors.value?.[field]?.[0] || '';
}

onMounted(() => {
  loadDashboard();
  loadExams();
  loadRanking();
});
</script>