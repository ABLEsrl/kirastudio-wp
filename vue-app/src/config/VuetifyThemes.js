import DefaultTheme from '@/config/DefaultTheme';

const lightColors = {
  ...DefaultTheme.colors,
  'background-1': DefaultTheme.colors['background-2'],
  surface: DefaultTheme.colors.white,
  'on-success': '#FFFFFF',
  'on-warning': '#FFFFFF',
};

const darkColors = {
  ...DefaultTheme.colors,
  background: '#24282e',
  'background-1': '#2d3139',
  'background-2': '#2d3139',
  surface: '#24282e',
  border: '#3a3f49',
  text: '#f3f4f6',
  white: '#ffffff',
  'on-success': '#FFFFFF',
  'on-warning': '#FFFFFF',
};

export const light = {
  dark: false,
  colors: lightColors,
  variables: {
    'activated-opacity': 0.1,
  },
};

export const dark = {
  dark: true,
  colors: darkColors,
  variables: {
    'activated-opacity': 0.1,
  },
};
