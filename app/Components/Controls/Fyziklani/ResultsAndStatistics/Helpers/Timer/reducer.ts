import { ResponseData } from 'FKSDB/Components/Controls/Fyziklani/ResultsAndStatistics/Helpers/Downloader/Downloader';
import {
    ACTION_FETCH_SUCCESS,
    ActionFetchSuccess,
} from 'vendor/fykosak/nette-frontend-component/src/fetch/redux/actions';
import { DataResponse } from 'vendor/fykosak/nette-frontend-component/src/Responses/response';

export interface State {
    gameEnd?: Date;
    gameStart?: Date;
    inserted?: Date;
    toEnd?: number;
    toStart?: number;
    visible?: boolean;
}

const fetchSuccess = (state: State, action: ActionFetchSuccess<DataResponse<ResponseData>>): State => {
    const {times, gameEnd, gameStart, times: {toEnd, toStart}} = action.data.data;
    return {
        ...state,
        ...times,
        gameEnd: new Date(gameEnd),
        gameStart: new Date(gameStart),
        inserted: new Date(),
        toEnd: toEnd * 1000,
        toStart: toStart * 1000,
    };
};

export const fyziklaniTimer = (state: State = {}, action): State => {
    switch (action.type) {
        case ACTION_FETCH_SUCCESS:
            return fetchSuccess(state, action);
        default:
            return state;
    }
};
