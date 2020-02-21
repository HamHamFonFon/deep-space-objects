// https://www.cloudways.com/blog/symfony-vuejs-app/
// https://fr.vuejs.org/v2/examples/select2.html
import '../css/app.scss';

import Vue from 'vue';
import Header from './Widgets/App/Header'
import Footer from './Widgets/App/Footer'

// Import custom icons
import SvgIcon from 'vue-svgicon'
Vue.use(SvgIcon, {
  tagName: 'svgicon'
});

// Header
new Vue({
  render: h => h(Header),
}).$mount(`#appHeader`);

// Footer
new Vue({
    render: h => h(Footer)
}).$mount(`#appFooter`);

// Google analytics
/*import Analytics from 'analytics';
import googleAnalytics from '@analytics/google-analytics';

const analytics = Analytics({
  app: 'astro.otter.space',
  version: 100,
  plugins: [
    googleAnalytics({
      trackingId: 'UA-158825900-1',
    })
  ]
});

analytics.page();*/


