<script>
import AbstractEntityTable from '@wexample/symfony-design-system/components/collection/table/abstract-entity-table/abstract-entity-table.vue';
import User from '@wexample/symfony-user/Entity/User';

const T = 'WexampleSymfonyUserDsBundle.components.account-table.account-table::';

// The tone of each state an account stands in.
const STATE_TONES = {
  active: 'success',
  waiting: 'info',
  disabled: 'warning',
  locked: 'danger'
};

// Every account, as its administrators read them: searched, sorted, paged
// by the api. Each row opens its account.
export default {
  extends: AbstractEntityTable,

  template: '#vue-template-wexample-symfony-user-ds-bundle-components-account-table-account-table',

  data() {
    return {
      fill: true,
      sticky: true,
      searchable: true,
      searchPlaceholder: this.trans(`${T}search`),
      showCount: true,
      pageLength: 25,
      defaultSort: { key: 'email', direction: 'asc' }
    };
  },

  methods: {
    getEntityClass() {
      return User;
    },

    getStateKey() {
      return 'accounts';
    },

    getColumnsConfiguration() {
      const t = (key) => this.trans(`${T}${key}`);

      return [
        {
          key: 'label',
          label: t('column.account'),
          href: {
            route: 'user_accounts_show',
            parameters: (row) => ({ id: row.id })
          }
        },
        { key: 'email', label: t('column.email'), secondary: true, sortable: true },
        {
          key: 'roles',
          label: t('column.roles'),
          format: (value) => value.join(', ')
        },
        {
          key: 'state',
          label: t('column.state'),
          cell: 'status',
          width: 's',
          format: (value) => ({ type: STATE_TONES[value] ?? 'neutral', label: t(`state.${value}`) })
        },
        { key: 'dateLastLogin', label: t('column.last_login'), cell: 'date', sortable: true }
      ];
    }
  }
};
</script>
