import { ResponseData } from 'FKSDB/Components/Game/ResultsAndStatistics/Helpers/Downloader/downloader';
import MainComponent from 'FKSDB/Components/Game/ResultsAndStatistics/Helpers/main-component';
import * as React from 'react';
import { app } from '../reducers/store';
import { NetteActions } from 'vendor/fykosak/nette-frontend-component/src/NetteActions/netteActions';
import TeamStats from 'FKSDB/Components/Game/ResultsAndStatistics/Statistics/TeamStatistics/index';
import TasksStats from 'FKSDB/Components/Game/ResultsAndStatistics/Statistics/TaskStatistics/index';
import CorrelationStats from 'FKSDB/Components/Game/ResultsAndStatistics/Statistics/CorrelationStatitics/index';
import { availableLanguage, Translator } from '@translator/translator';

interface OwnProps {
    mode: 'correlation' | 'team' | 'task';
    actions: NetteActions;
    data: ResponseData;
    translator: Translator<availableLanguage>;
}

export default function StatisticsComponent(props: OwnProps) {
    const {mode} = props;
    let content = null;
    switch (mode) {
        case 'team':
        default:
            content = <TeamStats/>;
            break;
        case 'task':
            content = <TasksStats/>;
            break;
        case 'correlation':
            content = <CorrelationStats/>;
    }
    return <MainComponent
        app={app}
        data={props.data}
        actions={props.actions}
        translator={props.translator}>
        {content}
    </MainComponent>;
}
