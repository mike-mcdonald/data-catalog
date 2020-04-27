const Color = require('color');

const TINTS = {
  100: 0.9,
  200: 0.75,
  300: 0.6,
  400: 0.3
};

const SHADES = {
  600: 0.9,
  700: 0.6,
  800: 0.45,
  900: 0.3
};

function tint(color, intensity) {
  const r = Math.round(color.red() + (255 - color.red()) * intensity),
    g = Math.round(color.green() + (255 - color.green()) * intensity),
    b = Math.round(color.blue() + (255 - color.blue()) * intensity);
  return Color([r, g, b]);
}

function shade(color, intensity) {
  const r = Math.round(color.red() * intensity),
    g = Math.round(color.green() * intensity),
    b = Math.round(color.blue() * intensity);
  return Color([r, g, b]);
}

function mix(color1, color2, weight) {
  return Color(color1).mix(Color(color2), weight);
}

function generateColors(colorHex) {
  color = Color(colorHex);

  colors = {
    '100': '',
    '200': '',
    '300': '',
    '400': '',
    '500': color.hex(),
    '600': '',
    '700': '',
    '800': '',
    '900': ''
  };

  for (const t in TINTS) {
    colors[t] = tint(color, TINTS[t]).hex();
  }

  for (const s in SHADES) {
    colors[s] = shade(color, SHADES[s]).hex();
  }

  return colors;
}

module.exports = {
  theme: {
    fontFamily: {
      sans: ['Open Sans', 'sans-serif']
    },
    colors: {
      transparent: 'transparent',
      inherit: 'inherit',
      current: 'currentColor',
      black: mix('#000000', mix('#FF6666', '#005CB9', 0.5).hex(), 0.2).hex(),
      white: '#ffffff',
      gray: generateColors('#666666'),
      cyan: generateColors('#00A0AE'),
      orange: generateColors('#F58220'),
      red: generateColors('#FF6666'),
      green: generateColors('#66AD83'),
      blue: generateColors('#005CB9'),
      marine: generateColors('#99CCCC'),
      tangerine: generateColors('#FCB040'),
      fog: generateColors('#E7E8EA'),
      purple: generateColors(mix('#FF6666', '#005CB9', 0.5).hex())
    },
    extend: {
      height: {
        'screen-50': '50vh',
        'screen-66': '66vh',
        'screen-75': '75vh'
      },
      spacing: {
        '7': '1.75rem',
        '10': '2.5rem',
        '12': '3rem',
        '16': '4rem',
        '20': '5rem',
        '24': '6rem',
        '28': '7rem',
        '32': '8rem',
        '80': '20rem',
        '128': '32rem',
        '(screen-16)': 'calc(100vh - 4rem)'
      },
      inset: theme => {
        return {
          '2': theme('spacing.2'),
          '3': theme('spacing.3'),
          '4': theme('spacing.4'),
          '5': theme('spacing.5'),
          '6': theme('spacing.6'),
          '10': theme('spacing.10'),
          '12': theme('spacing.12'),
          '16': theme('spacing.16'),
          '20': theme('spacing.20'),
          '24': theme('spacing.24')
        };
      },
      borderWidth: {
        '6': '6px'
      },
      maxWidth: theme => {
        return {
          'screen-xl': theme('screens.xl')
        };
      },
      maxHeight: {
        xs: '20rem',
        sm: '30rem',
        '1/4': '25%',
        '1/3': '33.33%',
        '1/2': '50%',
        '3/4': '75%',
        '(screen-16)': 'calc(100vh - 4rem)'
      },
      boxShadow: {
        'md-light': '0 0 12px 8px rgb(255,255,255)'
      },
      zIndex: {
        '90': '90',
        '100': '100'
      }
    }
  },
  variants: {
    backgroundColor: ['responsive', 'odd', 'even', 'hover', 'focus'],
    borderColor: ['responsive', 'hover', 'focus'],
    borderWidth: ['responsive', 'first', 'last', 'hover', 'focus'],
    opacity: ['responsive', 'hover', 'focus', 'disabled']
  },
  plugins: []
};
