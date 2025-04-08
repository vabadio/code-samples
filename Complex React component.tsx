/*
* A complex React component for a landing page on an LMS.
*
* Client: fintech.
*/

import AddressesMatchDrawer from "@/components/analysis/AddressesMatchDrawer";
import BankAccountAnalysisTable from "@/components/analysis/BankAccountAnalysisTable";
import ErrorFeedback from "@/components/common/ErrorFeedback";
import { LoadingSpinner, ChevronDownIcon, CopyIcon, MapIcon, Popover, Toast } from "@camino-financial/quantum-ui";
import Notification from "@/components/common/Notification";
import GdsStatusMessage from "@/components/gdsStatus/GdsStatusMessage";
import GdsStatusNotification from "@/components/gdsStatus/GdsStatusNotification";
import NotificationsDrawer from "@/components/transactions/NotificationsDrawer";
import ApplicantDataLayout from "@/layouts/ApplicantDataLayout";
import BankAccountService, {
  Aggregate,
  HighCostDebtDetails,
  parseStressCallDataToAggregate
} from "@/services/bankAccountAnalysis";
import AccountService, { Account } from "@/services/bankAccounts";
import DeclineDecisionsService from "@/services/declineDecisions";
import GdsService from "@/services/gds";
import GdsDataEntryBankStatementCalcsService from "@/services/gdsDataEntryBankStatementCalcs";
import QUERY_KEYS from "@/services/queryKeys";
import { FormControl, MenuItem, Select, SelectChangeEvent } from "@mui/material";
import MuiLink from "@mui/material/Link";
import Tooltip from "@mui/material/Tooltip";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import Image from "next/image";
import Link from "next/link";
import { useRouter } from "next/router";
import { useMemo, useState } from "react";

function GdsPush({
  analysisId,
  isGdsPushStatusLoading,
  isGdsPushProcessing
}: {
  analysisId: string;
  isGdsPushStatusLoading: boolean;
  isGdsPushProcessing: boolean;
}) {
  const [isModalOpen, setIsModalOpen] = useState(false);
  const queryClient = useQueryClient();

  const {
    isPending,
    isError,
    mutate: pushDataToGds
  } = useMutation({
    mutationKey: ["gds-update-job", analysisId],
    mutationFn: async () => {
      const jobDetails = await GdsDataEntryBankStatementCalcsService.postGdsDataEntryBankStatementCalcs(analysisId);
      setIsModalOpen(true);
      return jobDetails;
    },
    onSuccess: () => {
      queryClient.refetchQueries({ queryKey: [QUERY_KEYS.GDS_STATUS, analysisId] });
    }
  });

  const handleButtonClick = () => {
    pushDataToGds();
  };

  return (
    <>
      <div>
        {isPending && (
          <p data-cy="gds-push-loading" className="text-darkgunmetalquantumblue text-sm">
            Requesting push...
          </p>
        )}
        {isError && (
          <p data-cy="gds-push-error" className="text-red-500 text-sm">
            Failed to push to GDS
          </p>
        )}

        <button
          data-cy="gds-push-button"
          className="
            bg-black rounded px-5 py-2 text-sm font-bold text-white
            disabled:bg-gray-100 disabled:text-gray-400
          "
          onClick={handleButtonClick}
          disabled={isPending || isGdsPushStatusLoading || isGdsPushProcessing}
        >
          Initiate GDS Push
        </button>
      </div>

      {isModalOpen && (
        <Notification
          id="gds-job-confirmation"
          iconType="info"
          text="
            Your request is currently being processed and will soon be updated.
            Please allow a few moments for these changes to be reflected in Case Center.
          "
        />
      )}
    </>
  );
}

interface ContentProps {
  analysisId: string;
  accounts: Account[];
}

function Content({ analysisId, accounts }: ContentProps) {
  const [isAddressesDrawerOpen, setIsAddressesDrawerOpen] = useState(false);
  const [isNotificationsOpen, setIsNotificationsOpen] = useState<boolean>(false);

  const { data, isLoading, isError } = useQuery({
    queryKey: ["bank-account-analysis", analysisId],
    queryFn: () => {
      return BankAccountService.getBankAccountAnalysis(analysisId);
    }
  });

  const sortedAccounts = useMemo<Account[]>(() => [...accounts].sort((a) => (a.primaryAccount ? -1 : 1)), [accounts]);
  const primaryAccount = sortedAccounts[0];
  const formatAccountName = (accountName: string) => `*${accountName.slice(-4)}`;

  const accountSelectorOptions = useMemo(
    () =>
      sortedAccounts.map((accountOption) => {
        const labelText = formatAccountName(accountOption.officialName);

        const labelTemplate = accountOption.primaryAccount ? (
          <>
            <span>{labelText}</span>
            <span className="ml-12">Operating account</span>
          </>
        ) : (
          labelText
        );

        const label = <div className="flex gap-2 items-center font-light">{labelTemplate}</div>;

        return {
          value: accountOption.accountId,
          label
        };
      }),
    [sortedAccounts]
  );

  const hasMultipleAccounts = useMemo(() => accountSelectorOptions.length > 1, [accountSelectorOptions]);
  const [selectedAccountId, setSelectedAccountId] = useState<string | undefined>(accountSelectorOptions[0]?.value);

  const aggregatesByAccountId = useMemo<Record<string, Aggregate[]>>(() => {
    const stressCalcs = data?.analysis.bankAccountStressCalcs ?? [];
    return stressCalcs.reduce(
      (acc, stressCalc) => {
        const { accountId } = stressCalc;
        if (!accountId) return acc;
        acc[accountId] = stressCalc.stressCalcs.slice(0, 3).reverse().map(parseStressCallDataToAggregate);
        return acc;
      },
      {} as Record<string, Aggregate[]>
    );
  }, [data]);

  const notificationsByAccountId = useMemo<Record<string, string[]>>(() => {
    const stressCalcs = data?.analysis.bankAccountStressCalcs ?? [];
    return stressCalcs.reduce(
      (acc, stressCalc) => {
        const { accountId } = stressCalc;
        if (!accountId) return acc;
        acc[accountId] = stressCalc.notifications;
        return acc;
      },
      {} as Record<string, string[]>
    );
  }, [data]);

  const highCostDebtsByAccountId = useMemo<Record<string, HighCostDebtDetails>>(() => {
    const stressCalcs = data?.analysis.bankAccountStressCalcs ?? [];
    return stressCalcs.reduce(
      (acc, stressCalc) => {
        const { accountId } = stressCalc;
        if (!accountId) return acc;
        acc[accountId] = { highCostDebtCount: stressCalc.highCostDebtCount ?? 0, last90Days: stressCalc.last90Days };
        return acc;
      },
      {} as Record<string, HighCostDebtDetails>
    );
  }, [data]);

  const selectedAccount = sortedAccounts.find((accountToMatch) => accountToMatch.accountId === selectedAccountId);
  const selectedAggregates = selectedAccountId === undefined ? [] : (aggregatesByAccountId[selectedAccountId] ?? []);
  const selectedNotifications = selectedAccountId ? (notificationsByAccountId[selectedAccountId] ?? []) : [];
  const selectedHighCostDebts = selectedAccountId ? highCostDebtsByAccountId[selectedAccountId] : undefined;

  if (isLoading) {
    return <LoadingSpinner id="bank-account-analysis-loading" />;
  }

  if (isError || !data) {
    return (
      <ErrorFeedback id="bank-account-analysis-error">
        <p>
          Oops! There seems to be a problem loading your bank information. Please make sure you&apos;re logged in by
          clicking here:&nbsp;
          <MuiLink href={`${process.env.NEXT_PUBLIC_LOGIN_URL}?redirect_url=${window.location.href}`}>
            Login to Quantum
          </MuiLink>
        </p>
        <p>
          If you&apos;re already logged in and still don&apos;t see any results, please try refreshing the page or get
          in touch with the technology team.
        </p>
        <p>No results found.</p>
      </ErrorFeedback>
    );
  }

  const accountId = primaryAccount?.fundationAccountId;

  const addressMatchDrawerTemplate = accountId ? (
    <AddressesMatchDrawer
      analysisId={analysisId}
      accountId={Number(accountId)}
      open={isAddressesDrawerOpen}
      onClose={() => setIsAddressesDrawerOpen(false)}
    />
  ) : null;

  const onChangeAccount = (event: SelectChangeEvent<string>) => {
    const { value } = event.target;
    setSelectedAccountId(value);
  };

  const renderValue = () => {
    if (!selectedAccount) return null;

    const { officialName, mask } = selectedAccount;
    const accountName = officialName ?? mask;

    return (
      <span className="flex items-center mr-4 text-base text-text-default">
        {selectedAccount.primaryAccount ? (
          <Image
            className="w-4 h-4 mr-1 pr-0.5 mb-0.5 pb-px select-none"
            src="/images/star.svg"
            alt="Star"
            width={24}
            height={24}
          />
        ) : null}

        <span data-cy="bank-account-mask" className="font-light">
          {formatAccountName(accountName)}
        </span>
      </span>
    );
  };

  const handleNotificationsOpen = () => {
    setIsNotificationsOpen(true);
  };

  const handleNotificationsClose = () => {
    setIsNotificationsOpen(false);
  };

  return (
    <>
      <section className="bg-lynxwhite py-6 px-10">
        <div className="flex gap-4">
          <div className="flex gap-2 items-center text-sm text-raisinblack grow -mt-2">
            <button
              data-cy="aggregates-notifications-btn"
              className={`h-12 flex gap-3 items-center mr-3 px-4 py-3 cursor-pointer border rounded
              ${selectedNotifications?.length > 0 ? "bg-orange-100 border-orange-600" : "bg-white border-snowbank"}`}
              onClick={handleNotificationsOpen}
            >
              <Image
                src={`/images/notification-icon${selectedNotifications?.length > 0 ? "-unread" : ""}.svg`}
                alt="Notification"
                width={24}
                height={24}
              />
            </button>

            {/* eslint-disable-next-line jsx-a11y/label-has-associated-control */}
            <label className="font-bold" htmlFor="bank-account-id">
              Bank Account For Analysis
            </label>

            {hasMultipleAccounts ? (
              <FormControl size="small">
                <Select
                  labelId="account-selector-label"
                  id="account-selector"
                  data-cy="bank-accounts-selector"
                  value={selectedAccountId}
                  onChange={onChangeAccount}
                  sx={{
                    ".MuiOutlinedInput-notchedOutline": { border: 0 },
                    ".MuiSelect-icon": { top: "8px" }
                  }}
                  IconComponent={ChevronDownIcon}
                  MenuProps={{
                    anchorOrigin: {
                      vertical: "bottom",
                      horizontal: "left"
                    },
                    transformOrigin: {
                      vertical: "top",
                      horizontal: "left"
                    }
                  }}
                  renderValue={renderValue}
                >
                  {accountSelectorOptions.map((accountOption) => (
                    <MenuItem key={accountOption.value} value={accountOption.value}>
                      {accountOption.label}
                    </MenuItem>
                  ))}
                </Select>
              </FormControl>
            ) : (
              <span className="ml-3 pl-0.5">{renderValue()}</span>
            )}
          </div>
          <Link
            data-cy="bank-account-analysis-source-data-link"
            href={`/svc/transactions/ui/analysis/${analysisId}/transactions?accountId=${selectedAccountId}`}
            className="
              flex gap-2 h-12 px-8 bg-white items-center
              text-darkgunmetalquantumblue text-sm font-bold underline
              border border-darkgunmetalquantumblue rounded ml-auto
            "
          >
            <Image src="/images/court.svg" alt="" width={24} height={24} />
            <span>Source of Data</span>
          </Link>
        </div>

        <Tooltip title="Address Match" placement="bottom-start" arrow>
          <button
            className="size-8 bg-snowbank flex items-center justify-center rounded mt-2"
            data-cy="open-addresses-match"
            onClick={() => setIsAddressesDrawerOpen(true)}
          >
            <MapIcon />
          </button>
        </Tooltip>
      </section>
      <div className="mt-20">
        <BankAccountAnalysisTable aggregates={selectedAggregates} />
      </div>
      <section className="mt-12 flex text-sm text-raisinblack justify-between gap-4 items-start">
        <p className="w-full">Total Number of High Cost Debts</p>
        <input
          data-cy="total-high-cost-debts"
          type="text"
          id="total-high-cost-debts"
          className="px-4 py-2 rounded-lg border border-gray11 w-full"
          disabled
          value={selectedHighCostDebts?.highCostDebtCount ?? ""}
        />
        <div
          className="
              px-4 py-2 flex justify-between border border-snowbank rounded-md text-sm text-raisinblack font-bold w-full
            "
        >
          {/* eslint-disable-next-line jsx-a11y/label-has-associated-control */}
          <label htmlFor="hcd-last-90-days">HCD in Last 90 Days</label>
          <input
            data-cy="hcd-last-90-days"
            type="checkbox"
            id="hcd-last-90-days"
            disabled
            checked={selectedHighCostDebts?.last90Days}
          />
        </div>
        <div className="w-full">
          <div
            className="
                px-4 py-2 flex justify-between text-sm text-raisinblack
                font-bold border border-snowbank rounded-md w-full
              "
          >
            {/* eslint-disable-next-line jsx-a11y/label-has-associated-control */}
            <label htmlFor="hcd-os-balance-over-100-k" className="text-slate-300">
              HCD o/s Balance &gt; 100K
            </label>
            <input data-cy="hcd-os-balance-over-100-k" type="checkbox" id="hcd-os-balance-over-100-k" disabled />
          </div>
          {/* TODO: ADD A TEST FOR THIS TEXT */}
          <p className="pt-2 px-2 text-xs text-slate-700">
            This field is currently unavailable. For any alterations, kindly execute them after pushing this data to
            GDS.
          </p>
        </div>
      </section>

      {addressMatchDrawerTemplate}

      {isNotificationsOpen && (
        <NotificationsDrawer
          open={isNotificationsOpen}
          handleClose={handleNotificationsClose}
          notifications={selectedNotifications}
        />
      )}
    </>
  );
}

export default function BankAnalysisPage() {
  const router = useRouter();
  const { analysisId } = router.query as { analysisId: string };
  const [isReasonForDenialCopied, setIsReasonForDenialCopied] = useState(false);

  /* TODO: HANDLE ERRORS */
  const { data: accounts, isLoading: isAccountsLoading } = useQuery({
    queryKey: [QUERY_KEYS.ACCOUNTS, analysisId],
    queryFn: () => AccountService.getAccounts(analysisId),
    enabled: router.isReady
  });

  /* TODO: HANDLE ERRORS */
  const {
    data: gdsStatus,
    isLoading: isGdsStatusLoading,
    isError: isGdsStatusError
  } = useQuery({
    queryKey: [QUERY_KEYS.GDS_STATUS, analysisId],
    queryFn: () => GdsService.getGdsStatus(analysisId),
    enabled: router.isReady,
    refetchInterval: 5000
  });

  /* TODO: HANDLE ERRORS */
  const { data: declineDecisions, isLoading: isDeclineDecisionsLoading } = useQuery({
    queryKey: [QUERY_KEYS.DECLINE_DECISIONS, analysisId],
    queryFn: () => DeclineDecisionsService.getDecisions(analysisId),
    enabled: router.isReady
  });

  const primaryAccount =
    accounts?.find((account) => account.primaryAccount === true) ?? (accounts?.length ? accounts[0] : null);

  if (!router.isReady) return null;
  if (isAccountsLoading) return <LoadingSpinner id="bank-accounts-loading" />;

  const hasRecommendedActions = !isDeclineDecisionsLoading && declineDecisions;

  const onCopyRuleStatus = () => {
    if (!declineDecisions) return;
    navigator.clipboard.writeText(declineDecisions.rulesStatus);
    setIsReasonForDenialCopied(true);
    setTimeout(() => {
      setIsReasonForDenialCopied(false);
    }, 8_000);
  };

  const recommendedActionsTemplate = hasRecommendedActions ? (
    <div data-cy="decline-decisions" className="relative">
      <Popover
        isTooltipStyle
        content={
          <div data-cy="auto-denial-tooltip">
            Reasons for Denial:
            <div className="flex">
              <button onClick={onCopyRuleStatus} className="flex pr-1 items-center">
                <CopyIcon className="text-xs" />
              </button>
              {declineDecisions.rulesStatus}
            </div>
          </div>
        }
        anchorOrigin={{
          vertical: "bottom",
          horizontal: "right"
        }}
        transformOrigin={{
          vertical: "top",
          horizontal: "right"
        }}
      >
        <button
          data-cy="decline-decisions-btn"
          className={`px-5 py-3 text-sm rounded flex items-center cursor-pointer
          ${declineDecisions.status === "Decline" ? "bg-red-100" : "bg-orange-100"}`}
        >
          <Image
            className="h-6 mr-2 rounded-full border-4 border-white"
            src="/images/error-bang.svg"
            alt=""
            width={24}
            height={24}
          />
          <p>
            Recommended Action{" "}
            <strong className={`${declineDecisions.status === "Decline" ? "text-red-500" : "text-orange-500"}`}>
              {declineDecisions.status}
            </strong>
          </p>
          {declineDecisions.rulesFailCount > 0 ? (
            <Image className="h-4 ml-2" src="/images/information.svg" alt="" width={16} height={16} />
          ) : null}
        </button>
      </Popover>
    </div>
  ) : null;

  const copiedReasonsToastTemplate = isReasonForDenialCopied ? (
    <Toast
      id="reason-for-denial-copied"
      text="Reasons for Denial Copied to clipboard"
      onClose={() => setIsReasonForDenialCopied(false)}
      iconType="success"
    />
  ) : null;

  return (
    <div className="flex flex-col items-center pb-36 px-10">
      <ApplicantDataLayout account={primaryAccount}>
        <div className="block bg-white max-w-8xl w-full mt-10 p-6 rounded-2xl">
          <header className="max-w-8xl w-full relative">
            <div className="w-full mb-10 flex justify-between items-center gap-2">
              <Link data-cy="aggregates-page-back-link" href="/svc/transactions/ui" className="font-bold flex">
                <Image src="/images/arrow-back.svg" width={24} height={24} alt="arrow back" className="mr-3" />
                <span>Go Back</span>
              </Link>

              <GdsPush
                analysisId={analysisId}
                isGdsPushStatusLoading={isGdsStatusLoading}
                isGdsPushProcessing={gdsStatus?.status === "processing"}
              />
              {!isGdsStatusLoading && gdsStatus ? <GdsStatusNotification status={gdsStatus.status} /> : null}
            </div>
            <div className="w-full pb-8 flex justify-between items-center gap-2 border-b-2 border-outerspace">
              <h1
                data-cy="bank-account-analysis-title"
                className="text-darkgunmetalquantumblue text-2xl font-extrabold"
              >
                Cash Flow Analysis
              </h1>

              {recommendedActionsTemplate}
            </div>
          </header>

          {/* TODO: MOVE THIS INTO THE <main> TAG */}
          <GdsStatusMessage isLoading={isGdsStatusLoading} isError={isGdsStatusError} data={gdsStatus} />

          <main className="mt-10 max-w-8xl w-full">
            <Content analysisId={analysisId} accounts={accounts || []} />
          </main>
        </div>
      </ApplicantDataLayout>
      {copiedReasonsToastTemplate}
    </div>
  );
}
