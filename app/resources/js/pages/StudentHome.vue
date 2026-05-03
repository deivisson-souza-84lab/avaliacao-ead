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

      <div v-else class="grid gap-4 md:grid-cols-2">
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
              <input type="radio" :name="`question-${question.id}`" class="mt-1" disabled>

              <span class="text-slate-700">
                {{ alternative.text }}
              </span>
            </label>
          </div>
        </article>
      </div>

      <p class="mt-5 text-sm text-slate-500">
        A seleção e submissão das respostas será implementada na próxima etapa.
      </p>
    </div>
  </section>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { api } from '../services/api';

const exams = ref([]);
const loading = ref(false);
const errorMessage = ref('');

const selectedExam = ref(null);
const selectedExamLoading = ref(false);
const selectedExamErrorMessage = ref('');

async function loadExams() {
  loading.value = true;
  errorMessage.value = '';

  try {
    const response = await api.get('/student/exams');

    exams.value = response.data || [];
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
}

onMounted(loadExams);
</script>