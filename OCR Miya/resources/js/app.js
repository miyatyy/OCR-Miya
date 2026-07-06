import './bootstrap';
import { createApp } from 'vue';


import 'bootstrap/dist/css/bootstrap.min.css';
import 'admin-lte/dist/css/adminlte.min.css';
import 'bootstrap/dist/js/bootstrap.bundle.min.js';
import 'admin-lte/dist/js/adminlte.min.js';

import ExampleComponent from './components/ExampleComponent.vue';

const app = createApp({});
app.component('example-component', ExampleComponent);
app.mount('#app');