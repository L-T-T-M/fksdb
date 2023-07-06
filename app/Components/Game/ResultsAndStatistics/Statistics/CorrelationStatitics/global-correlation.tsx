import { scaleLinear } from 'd3-scale';
import { Submits } from 'FKSDB/Models/ORM/Models/Fyziklani/submit-model';
import { TaskModel } from 'FKSDB/Models/ORM/Models/Fyziklani/task-model';
import { TeamModel } from 'FKSDB/Models/ORM/Models/Fyziklani/team-model';
import * as React from 'react';
import { useContext } from 'react';
import { connect } from 'react-redux';
import { Action, Dispatch } from 'redux';
import { setNewState } from '../../actions/stats';
import { calculateCorrelation, getTimeLabel } from '../Middleware/correlation';
import { calculateSubmitsForTeams } from '../Middleware/submits-for-teams';
import { Store } from 'FKSDB/Components/Game/ResultsAndStatistics/reducers/store';
import { TranslatorContext } from '@translator/context';

interface StateProps {
    submits: Submits;
    tasks: TaskModel[];
    teams: TeamModel[];
    firstTeamId: number;
    secondTeamId: number;
}

interface DispatchProps {
    onChangeFirstTeam(id: number): void;

    onChangeSecondTeam(id: number): void;
}

function GlobalCorrelation(props: StateProps & DispatchProps) {
    const translator = useContext(TranslatorContext);
    const color = scaleLinear<string, string>().domain([0, 1000 * 1000]).range(['#ff0000', '#ffffff']);
    const {submits, teams} = props;
    const submitsForTeams = calculateSubmitsForTeams(submits);
    const rows = [];
    teams.forEach((firstTeam) => {
        teams.forEach((secondTeam) => {
            if (secondTeam.teamId <= firstTeam.teamId) {
                return;
            }
            const {avgNStdDev, countFiltered, countTotal} = calculateCorrelation(
                Object.hasOwn(submitsForTeams, firstTeam.teamId) ? submitsForTeams[firstTeam.teamId] : {},
                Object.hasOwn(submitsForTeams, secondTeam.teamId) ? submitsForTeams[secondTeam.teamId] : {},
            );
            rows.push(<tr key={secondTeam.teamId + '__' + firstTeam.teamId}>
                <td>{firstTeam.name}</td>
                <td>{secondTeam.name}</td>
                <td style={{backgroundColor: color(avgNStdDev.average)}}>
                    {getTimeLabel(avgNStdDev.average, avgNStdDev.standardDeviation)}
                </td>
                <td>{countFiltered}</td>
                <td>{countTotal}</td>
                <td>
                        <span className="btn btn-outline-primary btn-sm" onClick={() => {
                            props.onChangeFirstTeam(firstTeam.teamId);
                            props.onChangeSecondTeam(secondTeam.teamId);
                        }}>Detail</span>
                </td>
            </tr>);

        });
    });
    return <table className="table table-striped table-sm">
        <thead>
        <tr>
            <th>{translator.getText('First team')}</th>
            <th>{translator.getText('Second team')}</th>
            <th>{translator.getText('Average')}</th>
            <th>{translator.getText('Under 2 minutes')}</th>
            <th>{translator.getText('Both teams')}</th>
        </tr>
        </thead>
        <tbody>{rows}</tbody>
    </table>;
}

const mapStateToProps = (state: Store): StateProps => {
    return {
        firstTeamId: state.statistics.firstTeamId,
        secondTeamId: state.statistics.secondTeamId,
        submits: state.data.submits,
        tasks: state.data.tasks,
        teams: state.data.teams,
    };
};

const mapDispatchToProps = (dispatch: Dispatch<Action<string>>): DispatchProps => {
    return {
        onChangeFirstTeam: (teamId) => dispatch(setNewState({firstTeamId: +teamId})),
        onChangeSecondTeam: (teamId) => dispatch(setNewState({secondTeamId: +teamId})),
    };
};

export default connect(mapStateToProps, mapDispatchToProps)(GlobalCorrelation);
