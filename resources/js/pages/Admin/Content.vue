<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import axios from '@/axios';
import { Head, Link, router } from '@inertiajs/vue3';

interface BlogLite { id: string; title: string; status?: boolean; published_at?: string | null; deleted_at?: string | null; author?: { id?: number; name?: string } }

const props = defineProps<{ active: BlogLite[]; trashed: BlogLite[] }>();

const softDelete = async (id: string) => {
  if (!confirm('Soft delete this blog?')) return;
  await axios.delete(route('api.blog.delete.soft', id));
  router.reload({ only: ['active', 'trashed'] });
};

const restore = async (id: string) => {
  await axios.post(route('api.blog.restore', id));
  router.reload({ only: ['active', 'trashed'] });
};

const hardDelete = async (id: string) => {
  if (!confirm('Permanently delete this blog? This cannot be undone.')) return;
  await axios.delete(route('api.blog.delete.hard', id));
  router.reload({ only: ['active', 'trashed'] });
};
</script>

<template>
  <Head title="Content Management" />
  <AppLayout :breadcrumbs="[{ title: 'Admin', href: '/admin' }, { title: 'Content', href: '/admin/content' }]">
    <div class="flex flex-col gap-6 p-6">
      <Card>
        <CardHeader>
          <CardTitle>Published & Draft Blogs</CardTitle>
          <CardDescription>View and manage public content</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="border-b">
                  <th class="px-3 py-2 text-left">Title</th>
                  <th class="px-3 py-2 text-left">Author</th>
                  <th class="px-3 py-2 text-left">Status</th>
                  <th class="px-3 py-2 text-left">Published</th>
                  <th class="px-3 py-2 text-right">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="b in props.active" :key="b.id" class="border-b">
                  <td class="px-3 py-2">
                    <Link :href="route('blog.show', b.id)" target="_blank" class="hover:underline">{{ b.title }}</Link>
                  </td>
                  <td class="px-3 py-2">{{ b.author?.name || '—' }}</td>
                  <td class="px-3 py-2">{{ b.status ? 'Published' : 'Draft' }}</td>
                  <td class="px-3 py-2">{{ b.published_at || '—' }}</td>
                  <td class="px-3 py-2 text-right">
                    <Button size="sm" variant="destructive" @click="softDelete(b.id)">Soft delete</Button>
                  </td>
                </tr>
                <tr v-if="props.active.length === 0">
                  <td class="px-3 py-4 text-center text-muted-foreground" colspan="5">No blogs</td>
                </tr>
              </tbody>
            </table>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Deleted Blogs</CardTitle>
          <CardDescription>Restore or permanently delete</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="border-b">
                  <th class="px-3 py-2 text-left">Title</th>
                  <th class="px-3 py-2 text-left">Author</th>
                  <th class="px-3 py-2 text-left">Deleted At</th>
                  <th class="px-3 py-2 text-right">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="b in props.trashed" :key="b.id" class="border-b">
                  <td class="px-3 py-2">{{ b.title }}</td>
                  <td class="px-3 py-2">{{ b.author?.name || '—' }}</td>
                  <td class="px-3 py-2">{{ b.deleted_at || '—' }}</td>
                  <td class="px-3 py-2 text-right space-x-2">
                    <Button size="sm" variant="outline" @click="restore(b.id)">Restore</Button>
                    <Button size="sm" variant="destructive" @click="hardDelete(b.id)">Hard delete</Button>
                  </td>
                </tr>
                <tr v-if="props.trashed.length === 0">
                  <td class="px-3 py-4 text-center text-muted-foreground" colspan="4">No deleted blogs</td>
                </tr>
              </tbody>
            </table>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>


