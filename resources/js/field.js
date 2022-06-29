import DetailField from './components/DetailField'
import FormField from './components/FormField'

Nova.booting((app, store) => {
  app.component('detail-has-one-inject-field', DetailField)
  app.component('form-has-one-inject-field', FormField)
})
