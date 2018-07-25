console.log('I am a custom script! :)');

Vue.component('v-text', {
    props: {
        content: {
            type: String,
            required: true
        }
    },
    template: '<p v-html="content"></p>'
});

// const vm = new Vue({
//     el: '#page-wrapper'
// });