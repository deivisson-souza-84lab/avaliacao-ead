<template>
  <section>
    <div>
      <p class="text-sm font-semibold uppercase tracking-wide text-emerald-600">
        Área do Aluno
      </p>

      <h2 class="mt-2 text-3xl font-bold">
        Provas disponíveis
      </h2>

      <p class="mt-3 text-slate-600">
        Selecione uma prova disponível para visualizar as questões e enviar suas respostas.
      </p>
    </div>

    <div class="mt-8">
      <div v-if="loading" class="rounded-xl border border-slate-200 bg-slate-50 p-5 text-sm text-slate-600">
        Carregando provas disponíveis...
      </div>

      <div v-else-if="errorMessage" class="rounded-xl border border-red-200 bg-red-50 p-5 text-sm text-red-700">
        {{ errorMessage }}
      </div>

      <div v-else-if="exams.length === 0"
        class="rounded-xl border border-slate-200 bg-slate-50 p-5 text-sm text-slate-600">
        Nenhuma prova disponível no momento.
      </div>

      <div v-else>
        <div class="grid gap-4 md:grid-cols-2">
          <article v-for="exam in exams" :key="exam.id"
            class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-1 hover:shadow-md">
            <div class="flex h-full flex-col">
              <div>
                <h3 class="text-lg font-bold text-slate-900">
                  {{ exam.title }}
                </h3>

                <p class="mt-2 text-sm text-slate-600">
                  {{ exam.description || 'Sem descrição.' }}
                </p>
              </div>

              <div class="mt-5 flex flex-1 items-end">
                <button type="button"
                  class="rounded-xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700"
                  @click="selectExam(exam)">
                  Acessar prova
                </button>
              </div>
            </div>
          </article>
        </div>

        <div v-if="examsMeta && examsMeta.total > 0" class="mt-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
          <p class="text-sm text-slate-600">
            Mostrando {{ examsMeta.from }}–{{ examsMeta.to }} de {{ examsMeta.total }} provas disponíveis.
          </p>

          <div v-if="examsMeta.last_page > 1" class="flex flex-wrap gap-2">
            <button
              type="button"
              class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
              :disabled="loading || examsMeta.current_page === 1"
              @click="goToExamsPage(examsMeta.current_page - 1)"
            >
              Anterior
            </button>

            <button
              v-for="page in paginationPages(examsMeta)"
              :key="`student-exam-page-${page}`"
              type="button"
              class="rounded-lg border px-3 py-2 text-xs font-semibold transition"
              :class="page === examsMeta.current_page
                ? 'border-emerald-600 bg-emerald-600 text-white'
                : 'border-slate-300 text-slate-700 hover:bg-slate-50'"
              :disabled="loading"
              @click="goToExamsPage(page)"
            >
              {{ page }}
            </button>

            <button
              type="button"
              class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
              :disabled="loading || examsMeta.current_page === examsMeta.last_page"
              @click="goToExamsPage(examsMeta.current_page + 1)"
            >
              Próxima
            </button>
          </div>
        </div>
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
          <p class="text-sm font-semibold uppercase tracking-wide text-emerald-600">
            Prova selecionada
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
            <label v-for="alternative in question.alternatives" :key="alternative.id"
              class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 p-3 text-sm transition hover:bg-slate-50">
              <input v-model="answers[question.id]" type="radio" :name="`question-${question.id}`"
                :value="alternative.id" class="mt-1" :disabled="submitting || result" />

              <span class="text-slate-700">
                {{ alternative.text }}
              </span>
            </label>
          </div>
        </article>
      </div>

      <div class="mt-6 rounded-xl border border-slate-200 bg-white p-5">
        <h4 class="font-semibold text-slate-900">
          Identificação do aluno
        </h4>

        <div class="mt-4 grid gap-4 md:grid-cols-2">
          <div>
            <label class="text-sm font-medium text-slate-700" for="student_identifier">
              Identificador
            </label>

            <input id="student_identifier" v-model="studentIdentifier" type="text"
              class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100"
              placeholder="Ex.: aluno@email.com" :disabled="submitting || result">

            <p v-if="firstError('student_identifier')" class="mt-1 text-sm text-red-600">
              {{ firstError('student_identifier') }}
            </p>
          </div>

          <div>
            <label class="text-sm font-medium text-slate-700" for="student_name">
              Nome
            </label>

            <input id="student_name" v-model="studentName" type="text"
              class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100"
              placeholder="Ex.: Aluno 1" :disabled="submitting || result">

            <p v-if="firstError('student_name')" class="mt-1 text-sm text-red-600">
              {{ firstError('student_name') }}
            </p>
          </div>
        </div>

        <div v-if="submitErrorMessage" class="mt-4 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
          {{ submitErrorMessage }}
        </div>

        <button type="button"
          class="mt-5 rounded-xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:bg-slate-300"
          :disabled="submitting || result || !studentIdentifier || !allQuestionsAnswered()" @click="submitExam">
          {{ submitting ? 'Enviando...' : 'Enviar respostas' }}
        </button>
      </div>

      <div v-if="result" class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 p-5">
        <p class="text-sm font-semibold uppercase tracking-wide text-emerald-700">
          Resultado
        </p>

        <h4 class="mt-2 text-2xl font-bold text-emerald-950">
          {{ result.score }} de {{ result.total_questions }} acertos
        </h4>

        <p class="mt-2 text-emerald-800">
          Percentual final: <strong>{{ result.percentage }}%</strong>
        </p>

        <div class="mt-4 space-y-2">
          <div v-for="answer in result.answers" :key="`${answer.question_id}-${answer.alternative_id}`"
            class="rounded-xl bg-white p-3 text-sm">
            Questão {{ answer.question_id }}:
            <strong :class="answer.is_correct ? 'text-emerald-700' : 'text-red-700'">
              {{ answer.is_correct ? 'correta' : 'incorreta' }}
            </strong>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { api } from '../services/api';

const exams = ref([]);
const examsMeta = ref(null);
const examsPage = ref(1);
const examsPerPage = 10;
const loading = ref(false);
const errorMessage = ref('');

const selectedExam = ref(null);
const selectedExamLoading = ref(false);
const selectedExamErrorMessage = ref('');

const answers = ref({});
const studentIdentifier = ref('');
const studentName = ref('');

const submitting = ref(false);
const submitErrorMessage = ref('');
const submitErrors = ref({});
const result = ref(null);

async function loadExams(page = examsPage.value) {
  loading.value = true;
  errorMessage.value = '';

  try {
    const response = await api.get(`/student/exams?page=${page}&per_page=${examsPerPage}`);

    exams.value = response.data || [];
    examsMeta.value = response.meta || null;
    examsPage.value = examsMeta.value?.current_page || page;

    if (exams.value.length === 0 && examsPage.value > 1) {
      await loadExams(examsPage.value - 1);
    }
  } catch (error) {
    errorMessage.value = error.message || 'Não foi possível carregar as provas disponíveis.';
  } finally {
    loading.value = false;
  }
}

async function selectExam(exam) {
  selectedExam.value = null;
  selectedExamLoading.value = true;
  selectedExamErrorMessage.value = '';

  answers.value = {};
  studentIdentifier.value = '';
  studentName.value = '';
  submitErrorMessage.value = '';
  submitErrors.value = {};
  result.value = null;

  try {
    const response = await api.get(`/student/exams/${exam.id}`);

    selectedExam.value = response.data;
  } catch (error) {
    selectedExamErrorMessage.value = error.message || 'Não foi possível carregar a prova selecionada.';
  } finally {
    selectedExamLoading.value = false;
  }
}

function clearSelectedExam() {
  selectedExam.value = null;
  selectedExamErrorMessage.value = '';

  answers.value = {};
  studentIdentifier.value = '';
  studentName.value = '';
  submitErrorMessage.value = '';
  submitErrors.value = {};
  result.value = null;
}

async function submitExam() {
  if (!selectedExam.value) {
    return;
  }

  submitting.value = true;
  submitErrorMessage.value = '';
  submitErrors.value = {};
  result.value = null;

  const payload = {
    student_identifier: studentIdentifier.value,
    student_name: studentName.value || null,
    answers: selectedExam.value.questions.map((question) => ({
      question_id: question.id,
      alternative_id: answers.value[question.id],
    })),
  };

  try {
    const response = await api.post(`/student/exams/${selectedExam.value.id}/submit`, payload);

    result.value = response.data;
  } catch (error) {
    submitErrorMessage.value = error.message || 'Não foi possível submeter a prova.';
    submitErrors.value = error.errors || {};
  } finally {
    submitting.value = false;
  }
}

function firstError(field) {
  return submitErrors.value?.[field]?.[0] || '';
}

function paginationPages(meta) {
  return (meta?.links || [])
    .map((link) => link.page)
    .filter((page, index, pages) => page !== null && pages.indexOf(page) === index);
}

function goToExamsPage(page) {
  if (!examsMeta.value || page < 1 || page > examsMeta.value.last_page || page === examsMeta.value.current_page) {
    return;
  }

  clearSelectedExam();
  loadExams(page);
}

function allQuestionsAnswered() {
  if (!selectedExam.value?.questions?.length) {
    return false;
  }

  return selectedExam.value.questions.every((question) => answers.value[question.id]);
}

onMounted(() => loadExams());
</script>
