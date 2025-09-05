<template>
    <video ref="pub_video" class="bg-black" autoplay controls v-if="this.$root.publisher"></video>
    <video id="sub" ref="sub_video" class="bg-black" controls v-else></video>
</template>

<script lang="ts">
export default {
    name: 'VideoCall',
};
</script>
