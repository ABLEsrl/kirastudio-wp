export default {
  global: {
    rounded: "lg", // sm, md, lg, xl, pill, etc.
  },
  VBtnSoft: {
    variant: 'flat',
    rounded: 'pill',
    style: 'background-color: #f1f3f5; border: 1px solid #dee2e6; color: #333; box-shadow: none;',
  },
  VBtn: {
    variant: 'flat',
    density: 'default',
    size: 'default',
    ripple: true,
    elevation: 2,
  },
  VTextField: {
    variant: 'outlined',
    density: 'comfortable',
  },
  VTextarea: {
    variant: 'outlined',
    density: 'comfortable',
    clearIcon: '$mdiClose',
  },
  VSelect: {
    menuIcon: '$mdiChevronDown',
    clearIcon: '$mdiClose',
    density: 'comfortable',
    variant: 'outlined',
    color: 'primary',
  },
  VAlert: {
    variant: 'tonal',
  },
  VOverlay: {
    attach: '.v-application',
  },
  VDialog: {
    attach: '.v-application',
  },
  VMenu: {
    attach: '.v-application',
    transition: 'slide-y-transition',
  },
  VTooltip: {
    attach: '.v-application',
  },
  VSnackbar: {
    attach: '.v-application',
  },
  VBottomSheet: {
    attach: '.v-application',
  },
  VList: {
    density: 'compact',
  },
  VForm: {
    validateOn: 'input',
  },
};
