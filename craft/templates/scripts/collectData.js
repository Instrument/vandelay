var fse = require('fs-extra');
var fs = require('fs');
var axios = require('axios');
var Promise = require('bluebird');

const urls = {
  'local': 'snapchat.craft.dev/',
  'dev': 'snapchat-craft-dev.wrkbench.in/',
  'staging': 'snapchat-craft-staging.wrkbench.in/',
  'master': 'snapchat-craft-master.wrkbench.in/'
}

const environment = process.argv[2] || 'local';
const base = urls[environment] || urls['local'];
let languages;
let languageCount = 0;
console.log(`generating files from http://${base}`);


const localesUrl = 'actions/simpleApi/getLocales';

const collections = [
  {
    id: 'general',
    url: 'globals/generalTranslations'
  },
  {
    id: 'navigation',
    url: 'globals/headerNavigation'
  },
  {
    id: 'footer',
    url: 'globals/footer'
  },
  {
    id: 'legal',
    url: 'globals/legal'
  },
  {
    id: '404',
    url: 'globals/error404'
  },
  {
    id: 'pages',
    url: 'api/pages',
    vars: 'perpage=2',
  },
  {
    id: 'blog-entries',
    url: 'api/insightsAndNewsEntries',
    vars: 'perpage=2'
  },
  {
    id: 'success-stories-entries',
    url: 'api/successStoryEntries',
    vars: 'perpage=2'
  },
  {
    id: 'creative-spotlight-entries',
    url: 'api/creativeSpotlightEntries',
    vars: 'perpage=2'
  },
  {
    id: 'collections-storyProduct',
    url: 'category/product'
  },
  {
    id: 'collections-storyObjective',
    url: 'category/objective'
  },
  {
    id: 'collections-storyIndustry',
    url: 'category/industry'
  },
  {
    id: 'collections-storyRegion',
    url: 'category/region'
  },
  {
    id: 'blog-categories',
    url: 'category/blogType'
  }
];

//generic function promise wrapper
function wrap (genFn) { // 1
    var cr = Promise.coroutine(genFn) // 2
    return function (req, res, next) { // 3
        cr(req, res, next).catch(next) // 4
    }
}

//generate the array of axios get function for use in axios.all() below
function getRequests(urls) {
  var gets = [];
  for (var i=0; i<urls.length; i++) {
    gets.push(axios.get(urls[i]));
  };
  return gets;
}

var successPages = 0;
var spotlightPages = 0;
var blogPages = 0;
var pagedUrls = [];
//collects the data from the different craft end points and compiles them together into data files
var collect = wrap(function *(lang) {
  if (lang === undefined) {
    lang = '';
  }
  pagedUrls = [];
  
  lang = lang === 'en_us' ? '' : lang;
  console.log('collecting - ' + (lang ? lang : 'en'));

  const urls = [];
  for(var i=0; i<collections.length; i++) {
    urls.push(`http://${base}${collections[i].url}.json${(lang || collections[i].vars) ? '?' : ''}${lang ? `locale=${lang}` : ''}${(lang && collections[i].vars) ? '&' : ''}${collections[i].vars ? `${collections[i].vars}` : ''}`);
  }

  const singlesUrl = `http://${base}simpleapi/Singles`;
  console.log('loading: ', singlesUrl);
  axios.get(singlesUrl).then(function(singles) { // load the list of single handles
    for(var i=0; i<singles.data.entry.length; i++) {
      urls.push(`http://${base}api/${singles.data.entry[i]}.json${lang && `?locale=${lang}`}`);
      console.log('loading: ', urls[urls.length-1]);
    }
    axios.all(getRequests(urls))
    .then(axios.spread(function (generalTranslations, navigation, footer, legal, error404, pages, blogEntries, successStoriesEntries, creativeSpotlightEntries, categoryProduct, categoryObjective, categoryIndustry, categoryRegion, blogCategories) { // make sure to spread these in the same order as the items in the collections array

      if (!pages.data.data) {
        console.log('----------------PAGES DATA-----------------');
        console.dir(pages.data);
        console.log('----------------END PAGES DATA-----------------');
      }

      var step1Args = arguments;
      
      if (successStoriesEntries.data.meta.pagination.total_pages > 1) {
        for (var i=2; i<=successStoriesEntries.data.meta.pagination.total_pages; i++) {
          pagedUrls.push(`${successStoriesEntries.config.url}&page=${i}${lang && `&locale=${lang}`}`);
        }  
      }

      if (creativeSpotlightEntries.data.meta.pagination.total_pages > 1) {
        for (var i=2; i<=creativeSpotlightEntries.data.meta.pagination.total_pages; i++) {
          pagedUrls.push(`${creativeSpotlightEntries.config.url}&page=${i}${lang && `&locale=${lang}`}`);
        }  
      }
      if (blogEntries.data.meta.pagination.total_pages > 1) {
        for (var i=2; i<=blogEntries.data.meta.pagination.total_pages; i++) {
          pagedUrls.push(`${blogEntries.config.url}&page=${i}${lang && `&locale=${lang}`}`);
        }  
      }

      if (pages.data.meta.pagination.total_pages > 1) {
        for (var i=2; i<=pages.data.meta.pagination.total_pages; i++) {
          pagedUrls.push(`${pages.config.url}&page=${i}`);
        }
      }
      pagedUrls.forEach((url) => {
        console.log('loading: ', url);
      });
      //make sure can handle when there are no paged things to load (edge case);
      axios.all(getRequests(pagedUrls)).
      then(axios.spread(function() {
        var step2args = arguments; // paged elements responses
        if (successStoriesEntries.data.meta.pagination.total_pages > 1) { // concat paged successStory elements
          for(var i=0; i<successStoriesEntries.data.meta.pagination.total_pages-1; i++) {
            successStoriesEntries.data.data = successStoriesEntries.data.data.concat(step2args[i].data.data);
          }
        }
        var offset;
        if (creativeSpotlightEntries.data.meta.pagination.total_pages > 1) { // concat paged spotlight elements
          offset = successStoriesEntries.data.meta.pagination.total_pages > 1 ? successStoriesEntries.data.meta.pagination.total_pages - 1 : 0;
          for(var i=offset; i<offset + creativeSpotlightEntries.data.meta.pagination.total_pages-1; i++) {
            creativeSpotlightEntries.data.data = creativeSpotlightEntries.data.data.concat(step2args[i].data.data);
          }
        }

        if (blogEntries.data.meta.pagination.total_pages > 1) { // concat paged blog elements
          var offset2 = successStoriesEntries.data.meta.pagination.total_pages > 1 ? successStoriesEntries.data.meta.pagination.total_pages - 1 : 0;
          offset2 = creativeSpotlightEntries.data.meta.pagination.total_pages > 1 ? offset2 + creativeSpotlightEntries.data.meta.pagination.total_pages - 1 : offset2;
          for(var i=offset2; i<offset2 + blogEntries.data.meta.pagination.total_pages-1; i++) {
            blogEntries.data.data = blogEntries.data.data.concat(step2args[i].data.data);
          }
        }

        if (pages.data.meta.pagination.total_pages > 1) { // concat paged blog elements
          var offset3 = successStoriesEntries.data.meta.pagination.total_pages > 1 ? successStoriesEntries.data.meta.pagination.total_pages - 1 : 0;
          offset3 = creativeSpotlightEntries.data.meta.pagination.total_pages > 1 ? offset3 + creativeSpotlightEntries.data.meta.pagination.total_pages - 1 : offset3;
          offset3 = blogEntries.data.meta.pagination.total_pages > 1 ? offset3 + blogEntries.data.meta.pagination.total_pages - 1 : offset3;
          for(var i=offset3; i<offset3 + pages.data.meta.pagination.total_pages-1; i++) {
            pages.data.data = pages.data.data.concat(step2args[i].data.data);
          }
        }

        for(var i=collections.length; i<step1Args.length; i++){
          const identIndex = i - (step1Args.length - singles.data.entry.length);
          const ident = singles.data.entry[identIndex];
          switch(ident) {
            case 'home':
              var homeRef = step1Args[i].data.data[0];
              var homeHeroHeadline = homeRef && homeRef.homeHero && homeRef.homeHero[0] && homeRef.homeHero[0].headline_loc;

              var intro = {
                sectionTitle_loc: homeRef && homeRef.homeHero && homeRef.homeHero[0] && homeRef.homeHero[0].sectionTitle_loc, // TODO figure out where to get this from the cms
                sectionBackgroundColor: 'white',
                elements: [
                  {
                    type: 'home-page-headline',
                    formatting: 'vertically-center',
                    color: 'Yellow',
                    headline_loc: homeHeroHeadline,
                    ctaButtons: homeRef && homeRef.homeHero && homeRef.homeHero[0] && homeRef.homeHero[0].button && [
                      {
                        button: homeRef && homeRef.homeHero[0].button, // TODO: update to remove _loc from button
                        buttonStyle: 'primaryButton',
                        outline: homeRef && homeRef.homeHero[0].buttonOutline,
                        buttonType: homeRef && homeRef.homeHero[0].buttonStyle,
                        filled: true,
                        handle: homeRef && homeRef.homeHero[0].handle
                      }
                    ]
                  }
                ]
              }

              if (homeRef) {
                homeRef.pageBuilder.unshift(intro);  
                //inject the HOME page into the pages array
                pages.data.data.push(homeRef);
              }
              break;
            case "inspiration":
              var inspRef = step1Args[i].data.data[0];
              var selectOptions = [
                {
                  placeholder: generalTranslations.data.data[0].productFilterTitle_loc,  //TODO come back and replace this with data from craft
                  options: categoryProduct.data.data
                },
                {
                  placeholder: generalTranslations.data.data[0].objectiveFilterTitle_loc, //TODO come back and replace this with data from craft
                  options: categoryObjective.data.data
                },
                {
                  placeholder: generalTranslations.data.data[0].industryFilterTitle_loc, //TODO come back and replace this with data from craft
                  options: categoryIndustry.data.data
                }
              ];
              var selectOptionsSpotlight = [
                {
                  placeholder: generalTranslations.data.data[0].productFilterTitle_loc,  //TODO come back and replace this with data from craft
                  options: categoryProduct.data.data
                },
                {
                  placeholder: generalTranslations.data.data[0].objectiveFilterTitle_loc, //TODO come back and replace this with data from craft
                  options: categoryObjective.data.data
                },
                {
                  placeholder: generalTranslations.data.data[0].industryFilterTitle_loc, //TODO come back and replace this with data from craft
                  options: categoryIndustry.data.data
                },
                {
                  placeholder: generalTranslations.data.data[0].regionFilterTitle_loc, //TODO come back and replace this with data from craft
                  options: categoryRegion.data.data
                }
              ];
              var stories = {
                type: 'successStoriesGrid',
                selectOptions,
                stories: successStoriesEntries.data.data.slice(0, 5),
                hideFilters: true,
              }
              var spotlights = {
                type: 'successStoriesGrid',
                selectOptions: selectOptionsSpotlight,
                stories: creativeSpotlightEntries.data.data.slice(0, 5),
                hideFilters: true,
              }
              var elements = {
                sectionTitle_loc: inspRef && inspRef.title_loc,
                sectionBackgroundColor: 'white',
                elements: [
                  {
                    imageWithAlt: [],
                    headline_loc: inspRef &&inspRef.headline_loc,
                    subhead_loc: '',
                    ctaButtons: [],
                    type: 'centeredTextLockup'
                  },
                  {
                    type: 'headlineCTA',
                    headlineCopy: inspRef && inspRef.successStoriesTitle_loc,
                    buttonCopy: inspRef && inspRef.successStoriesButton && inspRef.successStoriesButton.customText_loc,
                    link: inspRef && inspRef.successStoriesButton && (inspRef.successStoriesButton.type === "entry" ? `/${inspRef.successStoriesButton.uri}` : `${inspRef.successStoriesButton.custom}`),
                  },
                  stories,
                  {
                    type: 'headlineCTA',
                    headlineCopy: inspRef &&inspRef.creativeSpotlightTitle_loc,
                    buttonCopy: inspRef && inspRef.creativeSpotlightButton && inspRef.creativeSpotlightButton.customText_loc,
                    link: inspRef && inspRef.creativeSpotlightButton && (inspRef.creativeSpotlightButton.type === "entry" ? `/${inspRef.creativeSpotlightButton.uri}` : `${inspRef.creativeSpotlightButton.custom}`),
                  },
                  spotlights,
                ]
              } 
              if (step1Args[i].data.data && step1Args[i].data.data[0]) {
                step1Args[i].data.data[0].pageBuilder = [elements];
                pages.data.data.push(step1Args[i].data.data[0]);  
              }
              break;
            case "insightsAndNews":
              var selectOptions = [
                {
                  placeholder: generalTranslations.data.data[0].allFilter_loc,  //TODO come back and replace this with data from craft
                  options: blogCategories.data.data
                },
                {
                  placeholder: generalTranslations.data.data[0].industryFilterTitle_loc, //TODO come back and replace this with data from craft
                  options: categoryIndustry.data.data
                },
                {
                  placeholder: generalTranslations.data.data[0].regionFilterTitle_loc, //TODO come back and replace this with data from craft
                  options: categoryRegion.data.data
                }
              ];

              blogEntries.data.data = blogEntries.data.data.map((blogPage) => {
                blogCategories.data.data.forEach((cat) => {
                  if (blogPage.entryType.indexOf(cat.slug)  !== -1) {
                    blogPage.entryTypeLabel = cat.title_loc;
                  }
                })
                return blogPage;
              });
              if (step1Args[i].data.data && step1Args[i].data.data[0]) {
                step1Args[i].data.data[0].pageBuilder = [{
                  sectionTitle_loc: "OverView", // TODO get this from 
                  sectionBackgroundColor: "white",
                  elements: [{
                    headline_loc: step1Args[i].data.data[0].headline_loc,
                    subhead_loc: "",
                    ctaButtons: [],
                    type: "centeredTextLockup"
                  },
                  {
                    type: "blog-grid",
                    formatting: "half-padding",
                    color: "black",
                    selectOptions,
                    cards: blogEntries.data.data,
                  }]
                }];
                pages.data.data.push(step1Args[i].data.data[0]);
              }
              break;
            case "successStories":
              var successRef = step1Args[i].data.data[0];
              var selectOptions = [
                {
                  placeholder: generalTranslations.data.data[0].productFilterTitle_loc,  //TODO come back and replace this with data from craft
                  options: categoryProduct.data.data
                },
                {
                  placeholder: generalTranslations.data.data[0].objectiveFilterTitle_loc, //TODO come back and replace this with data from craft
                  options: categoryObjective.data.data
                },
                {
                  placeholder: generalTranslations.data.data[0].industryFilterTitle_loc, //TODO come back and replace this with data from craft
                  options: categoryIndustry.data.data
                },
                {
                  placeholder: generalTranslations.data.data[0].regionFilterTitle_loc, //TODO come back and replace this with data from craft
                  options: categoryRegion.data.data
                }
              ];

              var intro = {
                sectionTitle_loc: successRef && successRef.title_loc,
                sectionBackgroundColor: 'white',
                elements: [
                  {
                    imageWithAlt: [],
                    headline_loc: successRef && successRef.headline_loc,
                    subhead_loc: '',
                    ctaButtons: [],
                    type: 'centeredTextLockup'
                  },
                  {
                    type: 'successStoriesGrid',
                    selectOptions,
                    stories: successStoriesEntries.data.data
                  }
                ]
              } 
              if (step1Args[i].data.data && step1Args[i].data.data[0]) {
                step1Args[i].data.data[0].pageBuilder = [intro];
                pages.data.data.push(step1Args[i].data.data[0]);  
              }
              break;
            case "creativeSpotlight":

              var creativeSpotRef = step1Args[i].data.data[0];
              var selectOptions = [
                {
                  placeholder: generalTranslations.data.data[0].productFilterTitle_loc,  //TODO come back and replace this with data from craft
                  options: categoryProduct.data.data
                },
                {
                  placeholder: generalTranslations.data.data[0].objectiveFilterTitle_loc, //TODO come back and replace this with data from craft
                  options: categoryObjective.data.data
                },
                {
                  placeholder: generalTranslations.data.data[0].industryFilterTitle_loc, //TODO come back and replace this with data from craft
                  options: categoryIndustry.data.data
                },
                {
                  placeholder: generalTranslations.data.data[0].regionFilterTitle_loc, //TODO come back and replace this with data from craft
                  options: categoryRegion.data.data
                }
              ];

              var intro = {
                sectionTitle_loc: creativeSpotRef && creativeSpotRef.title_loc,
                sectionBackgroundColor: 'white',
                elements: [
                  {
                    imageWithAlt: [],
                    headline_loc: creativeSpotRef && creativeSpotRef.headline_loc,
                    subhead_loc: '',
                    ctaButtons: [],
                    type: 'centeredTextLockup'
                  },
                  {
                    type: 'successStoriesGrid',
                    selectOptions,
                    stories: creativeSpotlightEntries.data.data
                  }
                ]
              } 
            
              if (step1Args[i].data.data && step1Args[i].data.data[0]) {
                step1Args[i].data.data[0].pageBuilder = [intro];
                pages.data.data.push(step1Args[i].data.data[0]);
              }
              break;
            default:
              if (step1Args[i].data.data && step1Args[i].data.data[0]) {
                pages.data.data.push(step1Args[i].data.data[0]);  
              }
              break;
          }
        }
        if (pages.data && pages.data.data && error404.data && error404.data.data && error404.data.data[0]) { //construct 404 page
          const errorPage = {
            "id": "*",
            "slug": "*",
            "color": "Yellow",
            "sidenav": false,
            "title_loc": "404",
            "pageBuilder": [
              {
                "sectionTitle_loc": error404.data.data[0].headline_loc,
                "sectionBackgroundColor": "white",
                "elements": [
                  {
                    "type": "error-page",
                    "titleLoc": error404.data.data[0].headline_loc,
                    "subTitleLoc": error404.data.data[0].subhead_loc,
                    "buttonPrimaryLabelLoc": error404.data.data[0].primaryCta.customText_loc,
                    "buttonPrimaryLink": "/",
                    "image": error404.data.data[0].image
                  }
                ]
              }
            ]
          }
          pages.data.data.push(errorPage);
        }

        var data = {
          general: generalTranslations.data.data[0],
          navigation: navigation.data.data,
          footer: footer.data.data,
          legal: legal.data.data,
          pages: pages.data.data,
          posts: {
            "categories": blogCategories.data.data,
            "posts": []
          },
          successStories: successStoriesEntries.data.data,
          blogs: blogEntries.data.data,
          spotlights: creativeSpotlightEntries.data.data,
        };
        //Generate routes file used in site constants
        var routes = [];
        for(var i=0; i<data.pages.length; i++){
          if (data.pages[i].slug !== "*") {
            routes.push(data.pages[i].slug);
          }
        }
        if (languageCount === 0) {
          fse.ensureDir(`src/utils`).then(() => {
            fs.writeFileSync(`src/utils/routes.json`, JSON.stringify(routes), {encode: 'utf8'});  
          });
        }
        
        fse.ensureDir(`public/locales${lang && `/${lang}`}`).then(() => {
          fs.writeFileSync(`public/locales${lang && `/${lang}`}/data.json`, JSON.stringify(data), {encode: 'utf8'});
          loadNextLang();
        });
      }))
      .catch(function(e){
        console.log(e);
      });



    }))
    .catch(function(e) {
      console.log('data service unavailable');
      console.log(e);
    });
  }).catch(function(e) {
    console.log('error loading singles');
    console.log(e);
  });
});

var loadNextLang = wrap(function *(lang) {
  if (process.env.LOCALE === '') {
    if (languageCount < languages.length - 2 && languages[languageCount].lang === 'en_us') {
      languageCount++;
    }
    if (languageCount < languages.length - 1) {
      collect(languages[languageCount].lang);
      languageCount++;
    }
  }
});

var langs = wrap(function *(lang) {
  console.log('LOCALE = ', lang);
  const url = `http://${base}${localesUrl}`;
  console.log('loading: ', url);
  axios.get(url).then(function(localesSet) { // load the list of single handles
    fse.ensureDir(`src/utils`).then(() => {
      fs.writeFileSync(`src/utils/locales.json`, JSON.stringify(localesSet.data), {encode: 'utf8'});  
    });

    languages = localesSet.data;
    
    if (lang !== '') {
      collect(lang);
    } else {
      collect(); // collect english
    }
  })
  .catch(function(e){
    console.log('error getting locales');
    console.log(e);
  });
});

langs(process.env.LOCALE);


