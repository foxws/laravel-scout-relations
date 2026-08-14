import type {ReactNode} from 'react';
import clsx from 'clsx';
import Heading from '@theme/Heading';
import styles from './styles.module.css';

type FeatureItem = {
  title: string;
  description: ReactNode;
};

const FeatureList: FeatureItem[] = [
  {
    title: 'Automatic re-indexing',
    description: (
      <>
        Add the <code>HasSearchableRelations</code> trait to a model and its
        related Searchable models are automatically re-indexed whenever it is
        saved with changes or deleted.
      </>
    ),
  },
  {
    title: 'Chunked and N+1 safe',
    description: (
      <>
        Re-indexing runs in chunks via <code>chunkById</code>, applying{' '}
        <code>makeAllSearchableUsing()</code> to each chunk query when
        defined to avoid N+1 queries.
      </>
    ),
  },
  {
    title: 'Cascade-safe by design',
    description: (
      <>
        A per-class re-entry guard prevents infinite cascades when models
        have mutual relationships, so re-indexing always stays bounded.
      </>
    ),
  },
];

function Feature({title, description}: FeatureItem) {
  return (
    <div className={clsx('col col--4')}>
      <div className="text--center padding-horiz--md">
        <Heading as="h3">{title}</Heading>
        <p>{description}</p>
      </div>
    </div>
  );
}

export default function HomepageFeatures(): ReactNode {
  return (
    <section className={styles.features}>
      <div className="container">
        <div className="row">
          {FeatureList.map((props, idx) => (
            <Feature key={idx} {...props} />
          ))}
        </div>
      </div>
    </section>
  );
}
