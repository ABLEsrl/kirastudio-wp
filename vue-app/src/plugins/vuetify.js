import 'vuetify/styles';
import Styles from '@/config/VuetifyStyles';
import { createVuetify } from 'vuetify';
import { VBtn } from 'vuetify/components';
import * as directives from 'vuetify/directives';
import { aliases, mdi } from 'vuetify/iconsets/mdi-svg';
import { dark, light } from '@/config/VuetifyThemes';
import * as Icons from '@/config/MdiIcons';

export default createVuetify({
  directives,
  aliases: {
    VBtnSoft: VBtn,
  },
  theme: {
    defaultTheme: 'light',
    variations: {
      colors: ['primary', 'secondary', 'warning', 'error', 'success'],
      lighten: 5,
      darken: 5,
    },
    themes: {
      light,
      dark,
    },
  },
  icons: {
    defaultSet: 'mdi',
    aliases: {
      ...aliases,
      ...Icons,
    },
    sets: {
      mdi,
    },
    dropdown: '$mdiChevronDown',
  },
  defaults: Styles,
});
