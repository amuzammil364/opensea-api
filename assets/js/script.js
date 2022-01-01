// new Splide( '.splide' ).mount();
document.addEventListener( 'DOMContentLoaded', ()=> {
    new Splide( '.splide', {
        type       : 'loop',
        perPage    : 2,
        height: '35rem',
        breakpoints: {
          980: {
            perPage    : 1,
        },
        },
      } ).mount();
});




