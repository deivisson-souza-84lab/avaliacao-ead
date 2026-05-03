<template>
  <main class="min-h-screen bg-slate-100 text-slate-900">
    <section class="mx-auto max-w-6xl px-6 py-10">
      <header class="mb-10">
        <p class="text-sm font-semibold uppercase tracking-wide text-indigo-600">
          Avaliação EAD
        </p>

        <h1 class="mt-2 text-4xl font-bold tracking-tight">
          Plataforma de provas online
        </h1>

        <p class="mt-3 max-w-2xl text-slate-600">
          Crie provas, disponibilize para alunos, corrija automaticamente as respostas
          e acompanhe os resultados em dashboard.
        </p>
      </header>

      <section v-if="currentView === 'home'" class="grid gap-6 md:grid-cols-2">
        <button
          type="button"
          class="rounded-2xl bg-white p-8 text-left shadow-sm ring-1 ring-slate-200 transition hover:-translate-y-1 hover:shadow-md"
          @click="currentView = 'teacher'"
        >
          <span class="text-sm font-semibold uppercase tracking-wide text-indigo-600">
            Professor
          </span>

          <h2 class="mt-3 text-2xl font-bold">
            Gerenciar provas
          </h2>

          <p class="mt-3 text-slate-600">
            Cadastre provas, questões, alternativas, edite conteúdos e acesse o dashboard.
          </p>
        </button>

        <button
          type="button"
          class="rounded-2xl bg-white p-8 text-left shadow-sm ring-1 ring-slate-200 transition hover:-translate-y-1 hover:shadow-md"
          @click="currentView = 'student'"
        >
          <span class="text-sm font-semibold uppercase tracking-wide text-emerald-600">
            Aluno
          </span>

          <h2 class="mt-3 text-2xl font-bold">
            Realizar provas
          </h2>

          <p class="mt-3 text-slate-600">
            Visualize provas disponíveis, responda questões e veja sua pontuação.
          </p>
        </button>
      </section>

      <section v-else class="rounded-2xl bg-white p-8 shadow-sm ring-1 ring-slate-200">
        <button
          type="button"
          class="mb-6 text-sm font-semibold text-indigo-600 hover:text-indigo-800"
          @click="currentView = 'home'"
        >
          ← Voltar
        </button>

        <TeacherHome v-if="currentView === 'teacher'" />

        <StudentHome v-if="currentView === 'student'" />
      </section>
    </section>
  </main>
</template>

<script setup>
import { ref } from 'vue';
import TeacherHome from './pages/TeacherHome.vue';
import StudentHome from './pages/StudentHome.vue';

const currentView = ref('home');
</script>